# 11. API Specs

> PrestoWorld sở hữu một hệ thống API **đa tầng**: từ nội bộ (Dashboard, Widget CSR), đến
> Plugin Marketplace (Cloudflare Worker + R2), rồi mở rộng thành **Super Fast API** cấp
> Enterprise (scale hàng tỉ request) và **Collab API** thời gian thực.

**Sơ đồ tổng quan**:

```
┌──────────────────────────────────────────────────────────────────┐
│                             CLIENT                               │
│   Dashboard (SolidJS) · Widget CSR (Bridge.js) · App · gRPC      │
└───────────────────────────────┬──────────────────────────────────┘
                                │
        ┌───────────────────────┼───────────────────────┐
        │ S1                    │ S2                     │ S3
┌───────▼────────┐   ┌──────────▼─────────┐   ┌─────────▼──────────┐
│  Plugin Mkt API│   │  Internal API      │   │  Public Dev API    │
│  Cloudflare     │   │  PrestoWorld Core  │   │  Spiral (REST+gRPC)│
│  Worker + R2    │   │  Spiral + RoadRunner│   │  Headless CMS      │
└───────┬────────┘   └──────────┬─────────┘   └─────────┬──────────┘
        │                      │                       │
        └──────────────────────┼───────────────────────┘
                               │ Enterprise mode
                    ┌──────────▼───────────┐
                    │  API Gateway (Edge)   │  Durable Objects cache < 10ms
                    │  CQRS · Queues · DO   │  Write path → Queue / Read path → Edge
                    └──────────────────────┘
```

---

## 11.1 Tổng quan các tầng API

| Tầng | Vai trò | Nơi chạy | Tài liệu liên quan |
|------|---------|----------|--------------------|
| **Internal API** | Dashboard, Widget CSR, Health, Report | Spiral + RoadRunner | [08](./08-dashboard.md), [03 §3.7](./03-backend.md) |
| **Plugin Marketplace API** | App Store: browse/install/rollback/scan | Cloudflare Worker + R2 | [07 §7.2](./07-cloudflare.md) |
| **Public Developer API** | User tự build API cho widget/app | Spiral (REST) + RoadRunner gRPC | [03 §3.7.2](./03-backend.md) |
| **Enterprise API** | Scale tỉ request, CQRS, Edge cache | Cloudflare Workers + Queues + DO | [09 §9.5](./09-roadmap.md) |
| **Collab / Real-time API** | Presence, OT/CRDT, silent-fix | WebSocket Gateway + Durable Objects | — |
| **Cron & Webhook API** | Trigger job, notify Slack/Discord | CLI + Cloudflare Cron Trigger + RR | [09 §9.5.4](./09-roadmap.md) |

### 11.1.1 Chế độ Individual vs Enterprise

- **Individual (Monolithic)**: PHP xử lý API trực tiếp. Cấu hình `mode: "individual"`.
- **Enterprise**: PHP chỉ giữ **Business Logic**; lớp request đầu vào là Cloudflare Workers.
  Mọi giao dịch có thể **hot-swap** giữa 2 chế độ bằng flag trong Cloudflare KV mà không
  cần khởi động lại ([nguồn: Config Orchestrator] — wp-config.php chỉ là Legacy Adapter).

```json
{
  "version": "2.0",
  "mode": "enterprise",
  "drivers": {
    "database": { "individual": "Presto\\Drivers\\DB\\PostgresLocal",
                  "enterprise": "Presto\\Drivers\\DB\\CitusProxy" },
    "storage":  { "individual": "Presto\\Drivers\\Storage\\LocalStorage",
                  "enterprise": "Presto\\Drivers\\Storage\\CloudflareR2" },
    "cache":    { "individual": "Presto\\Drivers\\Cache\\Redis",
                  "enterprise": "Presto\\Drivers\\Cache\\CloudflareKV" }
  },
  "telemetry": {
    "error_reporting": "enabled",
    "masking_level": "strict",
    "endpoint": "https://api.prestoworld.dev/v1/report"
  }
}
```

---

## 11.2 Authentication & Authorization

PrestoWorld dùng **PASETO V4 (Ed25519)** làm cơ chế token mặc định, không phải JWT:

- **Secure by default**: không chứa thuật toán "đám mơ hồ" như JWT (`alg=none`, algorithm
  agility attack).
- **Stateless**: mọi node giải mã token bằng Public Key từ State Registry mà không cần
  truy vấn lại Main DB → scale ngang no-file.

### 11.2.1 Luồng xác thực

```
1. Node xác thực user qua Main DB (Postgres).
2. Node ký PASETO token: { user_id, role, session_version }.
3. Client lưu token (HttpOnly Cookie hoặc Authorization: Bearer).
4. Request sau → bất kỳ node nào cũng giải mã bằng Public Key.
5. Blacklist token (revocation) check trong RAM trước, hết hạn/từ chối nhanh.
```

```php
namespace App\Security;

use ParagonIE\Paseto\Protocol\Version4;
use ParagonIE\Paseto\Parser;

final class PasetoAuthenticator
{
    public function authenticateRequest(string $token): ?UserIdentity
    {
        try {
            $decoded = Version4::parse($token, $this->getPublicKey());
            $claims  = $decoded->getClaims();

            // Blacklist check (RAM) — O(1)
            if ($this->blacklist->contains((string) $claims['jti'])) {
                return null;
            }

            return new UserIdentity(
                id: (string) $claims['user_id'],
                role: (string) $claims['role'],
                sessionVersion: (int) $claims['session_version'],
            );
        } catch (\Throwable) {
            return null; // Token sai hoặc hết hạn
        }
    }
}
```

### 11.2.2 Password Hashing (Legacy Bridge)

Chuẩn **Argon2id** là đích đến; MD5 (WP Legacy) và Bcrypt (WP hiện đại) chỉ là fallback:

| Hash prefix | Thuật toán | Xử lý |
|-------------|-----------|--------|
| `$argon2id$` | Argon2id | Verify trực tiếp |
| `$2y$` | Bcrypt | Verify + nâng cấp lên Argon2id ngay khi login |
| `$P$` / 32 ký tự | MD5/PHPass | Verify + nâng cấp lên Argon2id ngay khi login |

`PasswordHasher` bao bọc toàn bộ (`App\Security\PasswordHasher`), fix cứng cho hàm
`wp_check_password` qua shim ([§10.4.7](./10-wordpress-compiler.md)).

### 11.2.3 Grading Auth theo mức scale

| Cấp độ | Cơ chế | Ghi chú |
|--------|--------|---------|
| Individual | PASETO + Argon2id | Session trong RAM worker |
| Enterprise | OIDC / SAML (Okta, Azure AD, Keycloak) | PrestoWorld là Service Provider; map Claims → User trong Main DB |
| Machine-to-machine | API Key (Bearer) trong Cloudflare KV | Dùng cho Worker ↔ hosting ([07 §7.6.1](./07-cloudflare.md)) |
| Cron trigger | Secret token | Cloudflare Cron Trigger → POST `/api/cron/ping` |

### 11.2.4 Secret Management

- **Pepper** (chuỗi muối bí mật) không bao giờ nằm trong code/file: lấy từ Environment
  Variable (RoadRunner) hoặc Cloudflare Secrets / Vault.
- Enterprise: toàn bộ secret nằm trong `Secret Manager`, wp-config.php có thể biến mất
  hoàn toàn.

---

## 11.3 Quy ước chung (Conventions)

### 11.3.1 Naming & Version

- Prefix `/api/v1/` cho mọi endpoint nội bộ & public cấp ổn định.
- Endpoint không version (vd `/pw-api/render-widget`) = endpoint runtime nội bộ, có thể
  thay đổi theo phiên bản Core.
- Plugin Marketplace API dùng namespace `marketplace` (vd `marketplace/plugins`).

### 11.3.2 Response Envelope

```json
{
  "success": true,
  "data": { },
  "meta": { "request_id": "req_01", "duration_ms": 12 },
  "error": null
}
```

Lỗi chuẩn hóa:

```json
{
  "success": false,
  "data": null,
  "error": {
    "code": "VALIDATION_FAILED",
    "message": "Invalid input",
    "details": { "field": "slug", "reason": "required" }
  }
}
```

Mã lỗi chung:

| Code | HTTP | Ý nghĩa |
|------|------|---------|
| `VALIDATION_FAILED` | 422 | Lớp `Filter` của Spiral bắt được input sai |
| `UNAUTHORIZED` | 401 | Thiếu/hết hạn token |
| `FORBIDDEN` | 403 | Đủ token nhưng thiếu role |
| `NOT_FOUND` | 404 | Entity/plugin không tồn tại |
| `RATE_LIMITED` | 429 | Vượt Token Bucket |
| `UPSTREAM_ERROR` | 502 | Lỗi từ R2/Worker/DB bên dưới |

### 11.3.3 Rate Limiting (Resource Governor)

Dùng thuật toán **Token Bucket** để đảm bảo Migrator / background job không bao giờ "ăn"
băng thông dành cho lớp API ưu tiên cao ([nguồn: Resource Governor]).

| Lớp | Ưu tiên | Giới hạn mặc định |
|-----|---------|-------------------|
| Lớp 1 – Realtime UI | Cao nhất | 100 req/s / user |
| Lớp 2 – Public Dev API | Cao | 60 req/s / key |
| Lớp 3 – Sync/Migration | Thấp | 10 req/s / site |

---

## 11.4 Internal API (Dashboard + Widget CSR)

Endpoint chạy trên Spiral + RoadRunner. Toàn bộ phục vụ Dashboard SolidJS
([08](./08-dashboard.md)) và Widget CSR ([04 §4.4](./04-frontend.md)).

### 11.4.1 Plugin Management

| Method | Route | Mô tả |
|--------|-------|-------|
| GET | `/api/v1/plugins` | Danh sách (filter: `search`, `category`, `type`, `status`) |
| POST | `/api/v1/plugins` | Install (`{ "slug": "..." }` download + scan + cài) |
| GET | `/api/v1/plugins/{id}` | Chi tiết (hooks, file, compile report) |
| POST | `/api/v1/plugins/{id}/activate` | Kích hoạt (native: manifest.json / legacy: ghi Sổ cái SQLite) |
| POST | `/api/v1/plugins/{id}/deactivate` | Tắt plugin |
| POST | `/api/v1/plugins/{id}/delete` | Gỡ cài đặt |
| POST | `/api/v1/plugins/{id}/compile` | Chạy Compiler → CompileReport ([10.8](./10-wordpress-compiler.md)) |
| GET | `/api/v1/plugins/{id}/compile-report` | Lấy report compile + compatibility score |
| GET | `/api/v1/plugins/{id}/hooks` | Hook Ledger (Sổ cái SQLite) |

```php
namespace App\Controller\Api;

use Spiral\Router\Annotation\Route;

final class PluginController extends BaseApiController
{
    #[Route(route: '/api/v1/plugins/<id:slug>/compile', methods: 'POST')]
    public function compile(string $id, CompilerService $compiler): array
    {
        $report = $compiler->compile($id);
        $this->bus->publish(new CompileFinished($report));
        return $this->ok($report);
    }
}
```

### 11.4.2 Settings & Mode Switch

| Method | Route | Mô tả |
|--------|-------|-------|
| GET | `/api/v1/settings` | Đọc toàn bộ config (Config Orchestrator) |
| PUT | `/api/v1/settings` | Ghi cấu hình (máy bay filter, mask level, drivers) |
| POST | `/api/v1/settings/mode` | Hot-swap Individual ⇄ Enterprise (`{ "mode": "enterprise" }`) |

### 11.4.3 Database Monitor

| Method | Route | Mô tả |
|--------|-------|-------|
| GET | `/api/v1/database/stats` | Bảng/query rate, slow query, dung lượng SQLite vs R2 |
| POST | `/api/v1/database/backup` | Backup (Postgres dump + snapshot SQLite) |
| POST | `/api/v1/database/optimize` | `VACUUM`/index maintenance |

### 11.4.4 Widget CSR (Render Bridge)

| Method | Route | Mô tả |
|--------|-------|-------|
| POST | `/pw-api/render-widget` | Render 1 widget PHP → HTML fragment (`widget_type`, `settings`) |

```php
// app/src/Controller/WidgetAjaxController.php
/**
 * Endpoint: /pw-api/render-widget
 */
public function render(array $payload): string
{
    $widgetClass = $payload['widget_type'] ?? null;
    $settings    = $payload['settings'] ?? [];

    if (!$this->registry->has($widgetClass)) {
        throw new NotFoundException('Widget type not found');
    }

    $widget = $this->registry->make($widgetClass); // DI Spiral
    return $widget->render($settings, isCsrRequest: true);
}
```

### 11.4.5 Health & Observability

| Method | Route | Mô tả |
|--------|-------|-------|
| GET | `/health` | `status`, `database`, `cache`, `workers` |
| GET | `/api/v1/metrics` | Prometheus format (hook_execution_time, slow_queries…) |
| POST | `/api/v1/error-reports` | Gửi/ghi report lỗi local trước khi sync lên Cloud |

---

## 11.5 Plugin Marketplace API (Cloudflare Worker + R2)

API "App Store" chạy trên Cloudflare Workers, dữ liệu trên R2, metadata có thể cache
bằng KV. **Nhiệm vụ**: phân phối plugin toàn cầu (latency < 10ms), scan bảo mật, version
control & rollback. Chi tiết handler ở [07 §7.2](./07-cloudflare.md).

| Method | Route | Mô tả |
|--------|-------|-------|
| GET | `marketplace/plugins` | Danh sách (search, category, sort) |
| GET | `marketplace/plugins/{slug}` | Chi tiết metadata (extract từ `readme.txt` khi upload) |
| GET | `marketplace/plugins/download?id={id}&v={ver}` | Stream gói `.zip` từ R2 về hosting |
| POST | `marketplace/plugins/upload` | Upload bản build (chỉ dev, kèm API Key) |
| POST | `marketplace/plugins/{slug}/scan` | Scan bảo mật trên Worker trước khi publish |
| POST | `marketplace/plugins/{slug}/rollback` | Trả về version cũ (R2 giữ nhiều version) |
| GET | `marketplace/translation-map` | Phân phối Translation Map mới (auto-update rule) |
| POST | `marketplace/error-report` | Nhận Error Report từ hosting ([11.9](#119-error-reporting--telemetry)) |

```typescript
// Cloudflare Worker: routes
if (url.pathname === '/marketplace/plugins' && method === 'GET') {
  return handlePluginList(request, env);          // R2/KV + search/filter
}
if (url.pathname === '/marketplace/plugins/download') {
  return handlePluginDownload(request, env);      // stream zip, verify signature
}
```

### 11.5.1 Metadata Auto-extraction

Khi dev upload plugin lên R2, Worker "bóc tách" metadata từ `readme.txt`:

```json
{
  "slug": "contact-form-7",
  "name": "Contact Form 7",
  "version": "5.9",
  "description": "...",
  "icon_url": "https://r2.prestoworld.dev/icons/cf7.svg",
  "requires_php": "8.1+",
  "tested_wp": false,
  "scanned_at": "2026-01-01T00:00:00Z",
  "scan_result": "clean"
}
```

### 11.5.2 Luồng Install & Sync

```
1. User bấm Install trên Dashboard.
2. SolidJS → Worker: POST marketplace/plugins/download.
3. Worker stream .zip từ R2 → hosting an toàn (signature check).
4. Hosting nạp vào Sổ cái SQLite (legacy hooks) hoặc manifest.json (native).
5. SQLite sync ngược lên IndexedDB → UI hiện "Has 15 hooks registered".
```

---

## 11.6 Public Developer API (Headless CMS)

Spiral cho phép developer tự build API chuẩn REST (và gRPC qua RoadRunner) ngay cạnh
Widget — biến PrestoWorld thành **"Headless CMS có sẵn giao diện kéo thả"**.

### 11.6.1 Structure

```
/app/src/Endpoint/
├── /Api
│   ├── BaseApiController.php
│   └── ProductController.php
└── /Widgets
```

```php
// app/src/Endpoint/Api/ProductController.php
final class ProductController extends BaseApiController
{
    #[Route(route: '/api/v1/products', methods: 'GET')]
    public function list(ProductRepository $products): array
    {
        // Spiral tự convert DTO/array sang JSON
        return $this->ok($products->findAll());
    }

    #[Route(route: '/api/v1/products', methods: 'POST')]
    public function create(ProductFilter $filter, ProductService $service): array
    {
        // Filter tự validate + ép kiểu, sai trả 422 chuẩn
        return $this->created($service->create($filter->getValidData()));
    }
}
```

### 11.6.2 Workflow Develop-API-1 lần, dùng nhiều nơi

```
User viết API quản lý kho → User kéo 1 Widget Elementor → Widget CSR gọi API đó → 
App Mobile cũng gọi cùng API. Một lần logic backend chạy cho mọi mặt trận.
```

### 11.6.3 gRPC (Microservices)

RoadRunner hỗ trợ gRPC native — dành cho enterprise cần API giữa các dịch vụ:

```bash
# app/grpc/product.proto
service ProductService {
  rpc List (ListRequest) returns (ListReply);
}
```

| Transport | Dùng cho | Độ trễ |
|-----------|----------|--------|
| REST (JSON) | Web, App, Widget CSR | ~10–30ms |
| gRPC | Microservices nội bộ, pipeline | ~1–5ms |
| WebSocket | Collab/realtime, silent-fix | — |

---

## 11.7 Enterprise API (Super Fast — scale tỉ request)

Kiến trúc khi `mode: enterprise`: PHP chỉ là Business Logic, request vào qua
**API Gateway tại Edge** (Cloudflare Workers).

### 11.7.1 Luồng request Enterprise

```
Client → Edge (Workers/Rust-Wasm)
   ├─ Cache hit → Durable Objects trả ngay (< 10ms)
   └─ Cache miss → Query Gate an toàn → Postgres Master (sharded/Citus)
```

### 11.7.2 CQRS — tách Read / Write

| Path | Cơ chế |
|------|--------|
| **Write path** | Mọi mutation → Message Queue (Cloudflare Queues/Kafka) → Worker Cluster ghi DB ổn định, tránh spike làm sập DB |
| **Read path** | Data cache tại Edge (KV/Durable Objects); người dùng đọc từ node gần nhất, không chạm server gốc |
| **Sharding** | Postgres chia shards theo entity; cắm thêm node khi lớn |

### 11.7.3 One-Click Up-scale (Dashboard)

```
1. Presto quét toàn bộ wp-content + DB.
2. Đồng bộ Media lên R2; Transformer nắn lại URL trong database.
3. Bật Proxy: toàn bộ traffic qua Cloudflare Worker API.
4. Mọi report lỗi (DataMasker) tự tổng hợp trên Dashboard Enterprise cho hàng triệu site.
```

### 11.7.4 Hot-swapping Drivers

Config Reader đọc flag từ Cloudflare KV; đổi `mode` → tráo Driver DB/Storage/Cache ngay
trong runtime, không restart server. Trạng thái: **statelessness** — wp-config.php biến
mất (thay bằng env/container/Cloudflare Secrets).

---

## 11.8 Collaboration & Real-time API

Hàng ngàn admin collab trên cùng Dashboard mà không "đè" nhau.

### 11.8.1 Thành phần

| Thành phần | Cơ chế | Mô tả |
|------------|--------|-------|
| **Presence API** | Durable Objects + WebSocket | "Shark Bình đang chỉnh sửa trang này" |
| **Granular Locking** | Section/Field lock | Khóa từng field đang sửa, không khóa cả trang |
| **OT / CRDT merge** | Merge theo timestamp + ưu tiên | Hai admin sửa cùng field → hợp nhất, không báo "đã bị thay đổi" |
| **Event Store** | Event Sourcing | Rollback tới bất kỳ thời điểm nào mà không restore DB |
| **Silent Fix** | WebSocket push | Bản vá xuống admin đang online, không cần reload |

### 11.8.2 Event Store Schema (Postgres sharded)

```sql
CREATE TABLE pw_event_store (
    id         BIGSERIAL PRIMARY KEY,
    entity_id  TEXT        NOT NULL,
    actor_id   TEXT        NOT NULL,
    payload    JSONB       NOT NULL,   -- JSON Delta (chỉ phần khác biệt)
    version    BIGINT      NOT NULL,
    created_at TIMESTAMPTZ NOT NULL DEFAULT now()
);
```

### 11.8.3 Contextual Reporting

Khi lỗi trong môi trường collab:

- Report gửi về có **actor_id** (admin nào đang thao tác).
- **Auto-Collision Detection**: Transformer nhận diện lỗi concurrency → đề xuất cách sửa.
- **Silent Fix**: bản vá mới tự cập nhật qua WebSocket cho mọi admin đang online.

---

## 11.9 Error Reporting & Telemetry

Phục vụ: hosting nhỏ (Individual) gửi lỗi ngược về PrestoTeam qua Cloudflare; Enterprise
tổng hợp hàng triệu site trên một Dashboard.

### 11.9.1 Luồng xử lý lỗi chủ động

```
1. Intercept:  $wpdb chặn lỗi — không để website "màn hình trắng".
2. Sanitize:   DataMasker loại bỏ mật khẩu / PII / API Key khỏi SQL lỗi.
3. Bundle:     đóng gói: query gốc, query đã transform, PHP version, plugin gây lỗi.
4. Send:       POST /marketplace/error-report (Cloudflare).
5. Aggregate:  Worker gộp lỗi giống nhau từ nghìn site → auto nâng ưu tiên.
6. Storage:    Chi tiết lưu R2 dạng log file.
7. Auto-update: sửa xong chỉ cần đẩy rule mới lên → toàn cầu tự tải Translation Map.
```

### 11.9.2 Error Report mẫu

```json
{
  "report_id": "err_992831",
  "plugin_slug": "wp-legacy-plugin-x",
  "engine": "postgresql",
  "error_type": "syntax_mismatch",
  "context": {
    "original_query": "SELECT * FROM wp_users WHERE id = 'admin'",
    "transformed_query": "SELECT * FROM \"pw_users\" WHERE id = 'admin'",
    "db_error": "invalid input syntax for type integer: 'admin'"
  },
  "environment": {
    "php_version": "8.3",
    "prestoworld_version": "1.5.0",
    "transport": "https"
  },
  "actor_id": "admin_9f2c" ,
  "masking_level": "strict"
}
```

### 11.9.3 Cron Error Capture

Cron chạy ngầm nên lỗi khó thấy → Capture → DataMasker → Report về Cloud
("Job 'sync_inventory' của Plugin X lỗi") → Icon Cron nhấp nháy đỏ trên Dashboard.

---

## 11.10 Cron & Webhooks

### 11.10.1 Ba cơ chế trigger cron

| Cơ chế | Mô tả | Khuyến nghị |
|--------|-------|-------------|
| 1. Server crontab | `* * * * * php /path/to/presto core:cron-run` | Nếu hosting cho phép |
| 2. Cloudflare Cron Trigger | Worker gửi request bí mật kèm **token** mỗi phút → `POST /api/cron/ping` | Chính xác, không đụng hosting |
| 3. Virtual Cron (fallback) | "Ký sinh" vào request user, kích hoạt job bất đồng bộ | Không có crontab |

### 11.10.2 Webhook API (Milestone 5.4)

| Method | Route | Mô tả |
|--------|-------|-------|
| GET | `/api/v1/webhooks` | Liệt kê webhook |
| POST | `/api/v1/webhooks` | Tạo webhook (`{ "event": "plugin.installed", "url": "...", "secret": "..." }`) |
| POST | `/api/v1/webhooks/{id}/test` | Gửi test ping |
| DELETE | `/api/v1/webhooks/{id}` | Xóa |

Sự kiện có sẵn:

```
plugin.installed        plugin.activated       plugin.deactivated
compile.finished        report.error           database.backup_done
site.scaled             settings.mode_changed
```

Payload webhook ký HMAC bằng `secret`; nhận qua Slack/Discord/CI cho notification
([09 §9.5.4](./09-roadmap.md)).

---

## 11.11 Security & Best Practices

| Chủ đề | Quy tắc |
|--------|---------|
| **Auth** | PASETO V4 Ed25519; OIDC/SAML cho enterprise; API Key (Bearer) trong KV cho M2M |
| **Password** | Argon2id mặc định; MD5/Bcrypt chỉ fallback + "upgrade on login" |
| **Input** | Lớp `Filter` của Spiral (validate + ép kiểu → 422) — không đụng `$_POST` |
| **Secret** | Pepper & token trong env/Secrets/Vault — không bao giờ trong file |
| **CORS** | Khóa theo domain; Marketplace API mở `*` nhưng phải API Key khi ghi |
| **Rate limit** | Token Bucket 3 lớp (realtime > public > sync/migration) |
| **Masking** | DataMasker + mask level per-tenant từ Config Reader ([05 §5.6](./05-database.md)) |
| **M2M cơ chế Worker↔hosting** | Signature/API Key; cron ping kèm token bí mật |

---

## 11.12 Liên hệ với Roadmap

| API | Milestone liên quan |
|-----|---------------------|
| Health/Internal cơ bản | M1.1 (Basic HTTP endpoints) |
| Plugin Mirror API local | M2.2 (Plugin Mirror System) |
| Marketplace API (Worker+R2) | M2.2 / M2.4 (Dashboard) + [07](./07-cloudflare.md) |
| Render-widget CSR | M2.3 (Hybrid Rendering) |
| Enterprise API + One-click up-scale | M3.x (Enterprise) + [09 §9.5](./09-roadmap.md) |
| Webhook & notification | M5.4 (Platform Integration) |
| Compile-report API | M2.5 (AST Compiler) + [10 §10.8](./10-wordpress-compiler.md) |