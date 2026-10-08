# 02. Architecture - Kiến Trúc Tổng Thể

## 2.1 Cấu Trúc Tổng Quan

```
┌─────────────────────────────────────────────────────────────────┐
│                        CLIENT BROWSER                            │
├─────────────────────────────────────────────────────────────────┤
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐         │
│  │ SolidJS UI   │  │ IndexedDB    │  │ Bridge.js    │         │
│  │ (Dashboard)  │  │ (Cache)      │  │ (SPA Hybrid) │         │
│  └──────┬───────┘  └──────┬───────┘  └──────┬───────┘         │
│         │                 │                 │                  │
│         └─────────────────┴─────────────────┘                  │
│                            │                                   │
└────────────────────────────┼───────────────────────────────────┘
                             │ HTTPS/HTTP2
┌────────────────────────────┼───────────────────────────────────┐
│                    CLOUDFLARE EDGE                             │
├────────────────────────────┼───────────────────────────────────┤
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐         │
│  │ Cloudflare    │  │ Cloudflare    │  │ Cloudflare    │         │
│  │ Workers       │  │ R2 (Storage)  │  │ CDN           │         │
│  │ (API)         │  │ (Plugins)     │  │ (Assets)      │         │
│  └──────────────┘  └──────────────┘  └──────────────┘         │
└────────────────────────────┼───────────────────────────────────┘
                             │
┌────────────────────────────┼───────────────────────────────────┐
│                      APPLICATION SERVER                         │
├────────────────────────────┼───────────────────────────────────┤
│  ┌────────────────────────────────────────────────────────┐   │
│  │              RoadRunner (Golang)                         │   │
│  │  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐    │   │
│  │  │ HTTP Server  │  │ Worker Pool  │  │ WebSocket    │    │   │
│  │  └──────────────┘  └──────────────┘  └──────────────┘    │   │
│  └────────────────────────────────────────────────────────┘   │
│                              │                                   │
│  ┌────────────────────────────────────────────────────────┐   │
│  │           Witals Framework (PHP 8.3+)                    │   │
│  │  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐    │   │
│  │  │ Bootloaders  │  │ DI Container │  │ Event System │    │   │
│  │  └──────────────┘  └──────────────┘  └──────────────┘    │   │
│  └────────────────────────────────────────────────────────┘   │
│                              │                                   │
│  ┌────────────────────────────────────────────────────────┐   │
│  │              PrestoWorld Core Layers                    │   │
│  │  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐    │   │
│  │  │ Hook Manager │  │ Block        │  │ Widget       │    │   │
│  │  │ (Legacy)     │  │ Registry     │  │ Registry     │    │   │
│  │  └──────────────┘  └──────────────┘  └──────────────┘    │   │
│  └────────────────────────────────────────────────────────┘   │
└────────────────────────────┼───────────────────────────────────┘
                             │
┌────────────────────────────┼───────────────────────────────────┐
│                    DATABASE LAYER                               │
├────────────────────────────┼───────────────────────────────────┤
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐         │
│  │ PostgreSQL   │  │ SQLite       │  │ $wpdb        │         │
│  │ (Core Data)   │  │ (Legacy)     │  │ (Transformer)│         │
│  └──────────────┘  └──────────────┘  └──────────────┘         │
└─────────────────────────────────────────────────────────────────┘
```

---

## 2.2 Layer Breakdown

### Layer 1: Client Browser

**SolidJS Dashboard**:
- SPA (Single Page Application)
- Reactive UI với Signals
- TSX components (pre-compiled)
- No runtime framework overhead

**IndexedDB**:
- Cache HTML fragments
- Store plugin manifests
- Offline support for Page Builder
- Sync with SQLite registry

**Bridge.js**:
- Vanilla JS (~2KB)
- SPA navigation intercept
- Ajax fragment loading
- IndexedDB sync manager

### Layer 2: Cloudflare Edge

**Cloudflare Workers**:
- Plugin Marketplace API
- Pre-analysis of plugin packages
- Error report aggregation
- Hotfix distribution (Translation Maps)

**Cloudflare R2**:
- Plugin package storage (.zip)
- Icon, screenshot, manifest storage
- SQLite backup storage
- Global CDN distribution

**CDN**:
- Static asset distribution
- Edge caching for API responses
- DDoS protection

### Layer 3: Application Server

**RoadRunner (Golang)**:
- HTTP/2 server
- Worker pool management
- Persistent connections
- WebSocket support
- Worker recycling (prevent memory leaks)

**Witals Framework**:
- Bootloaders (module loading)
- DI Container (dependency injection)
- Event Dispatcher (Observer pattern)
- Pipeline (Filter pattern)
- Cycle ORM (database abstraction)
- Traditional PHP-FPM support (ngoài RoadRunner)

**PrestoWorld Core**:
- Hook Manager (Legacy compatibility)
- Controls System (PHP-driven UI)
- Widget Registry (auto-discovery)
- Configuration Reader (env/wp-config)
- WP Compiler (4-pass Transformer: Query/WPFunction/Class/UserFunction → [10](./10-wordpress-compiler.md))
- Shim Layer (Function/Class shims → [10.7](./10-wordpress-compiler.md))
- Block Registry (Gutenberg fork block renderer)

### Layer 4: Database Layer

**PostgreSQL**:
- Core data (users, posts, settings)
- Native plugin configurations
- JSONB for metadata
- Advanced indexing (GIN, GiST)

**SQLite**:
- Legacy plugin registry
- Legacy plugin data tables
- Hook ledger
- Portable backup format

**$wpdb Transformer**:
- MySQL → PostgreSQL translation
- MySQL → SQLite translation
- Query sanitization
- Error masking
- Full rule set: [10.3 WordPress Compiler](./10-wordpress-compiler.md)

---

## 2.3 Request Flow

### SSR Request (Initial Load)

```
1. Browser → Request URL
2. Cloudflare CDN → Check cache
3. If miss → RoadWorker
4. RoadRunner → Assign worker from pool
5. Spiral → Bootload (warm in RAM)
6. Controller → Fetch data from PostgreSQL
7. View → Render PHP Template (SSR)
8. Response → HTML + CSS/JS
9. Browser → Display page
10. Browser → Save state to IndexedDB
```

### CSR Request (SPA Navigation)

```
1. Browser → Click internal link
2. Bridge.js → Intercept event
3. Bridge.js → Check IndexedDB for fragment
4. If cache exists → Display immediately (0ms)
5. If cache miss → Fetch from API
6. Spiral → Render fragment (PHP Template)
7. Response → HTML fragment
8. Bridge.js → Update DOM
9. IndexedDB → Save fragment
```

### Legacy Plugin Execution

```
1. Core Event → trigger('save_post')
2. Hook Manager → Query SQLite registry
3. SQLite → Return list of callbacks
4. Invoker → Lazy-load source files
5. Shim Resolve → Function/Class transformer ([10.4/10.5](./10-wordpress-compiler.md))
6. $wpdb → Route query (PostgreSQL or SQLite)
7. Transformer → Translate MySQL syntax
8. Execute → Callback function
9. Result → Return to Core
10. If error → DataMasker → Send report to Cloudflare
```

---

## 2.4 Deployment Modes

### Mode A: Self-Managed (VPS/Dedicated)

**Characteristics**:
- RoadRunner with worker pool in RAM
- Direct PostgreSQL connection
- SQLite for legacy plugins
- Full control over configuration

**Optimizations**:
- Workers persist in RAM (sub-30ms)
- Connection pooling
- OpCache enabled
- Worker TTL configuration

### Mode B: Shared Hosting

**Characteristics**:
- PHP-FPM stateless mode
- SQLite for registry (file-based)
- OpCache for compiled registry
- Lazy plugin loading

**Optimizations**:
- Discovery runs on plugin change only
- Compiled registry in PHP file
- OpCache naps registry into Shared Memory
- Minimal I/O operations

### Mode C: SaaS (Cloud-Native)

**Characteristics**:
- Multi-tenant architecture
- Managed PostgreSQL
- RoadRunner auto-scaling
- Edge distribution

**Optimizations**:
- Horizontal scaling
- Load balancing
- Database sharding
- CDN caching

---

## 2.5 File Structure

```
/prestoworld
├── /app (Spiral Application)
│   ├── /src/Bootloader
│   │   ├── ConfigBootloader.php
│   │   ├── DatabaseBootloader.php
│   │   ├── LegacyBridgeBootloader.php
│   │   └── WidgetRegistryBootloader.php
│   ├── /src/Controller
│   │   ├── InstallController.php
│   │   ├── WidgetAjaxController.php
│   │   └── DashboardController.php
│   ├── /src/Core
│   │   ├── /Database
│   │   │   ├── PrestoWpdb.php
│   │   │   ├── QueryTransformer.php
│   │   │   └── DataMasker.php
│   │   ├── /Legacy
│   │   │   ├── LegacyRegistry.php
│   │   │   ├── LegacyInvoker.php
│   │   │   └── HookDiscovery.php
│   │   ├── /Blocks
│   │   │   ├── BaseBlock.php
│   │   │   ├── BlockRegistry.php
│   │   │   └── BlockRenderer.php
│   │   ├── Config
│   │   │   └── ConfigurationReader.php
│   │   ├── Compiler
│   │   │   ├── WpCompiler.php
│   │   │   ├── PluginScanner.php
│   │   │   ├── MappingRegistry.php
│   │   │   ├── SymbolAnalyzer.php
│   │   │   └── Passes
│   │   │       ├── QueryPass.php           (Pass 1)
│   │   │       ├── WpFunctionPass.php      (Pass 2)
│   │   │       ├── ClassPass.php           (Pass 3)
│   │   │       └── UserFunctionPass.php    (Pass 4 — user fn → static method)
│   │   └── Compatibility
│   │       ├── ShimLoader.php
│   │       └── LegacyState.php
│   └── /config
│       ├── database.php
│       └── cache.php
├── /public (Web Root)
│   ├── index.php (RoadRunner entry point)
│   └── /assets
│       ├── /js
│       │   ├── bridge.js
│       │   └── editor.js (SolidJS compiled)
│       ├── /gutenberg
│       │   ├── editor.js  (Gutenberg fork build)
│       │   └── editor.css
│       └── /css
│           └── dashboard.css
├── /storage
│   ├── /framework
│   │   ├── presto_registry.php (Compiled hooks)
│   │   └── presto_legacy.sqlite (Legacy data)
│   └── /logs
├── .rr.yaml (RoadRunner configuration)
├── docker-compose.yml
├── composer.json
└── app.php (Spiral entry point)
```

---

## 2.6 Security Architecture

### Input Validation
- Spiral Filter layer (Type-safe validation)
- SQL injection prevention via PDO prepared statements
- XSS prevention via template escaping

### Data Isolation
- Legacy plugins in SQLite sandbox
- Core data in PostgreSQL
- $wpdb Transformer as middleware

### Error Handling
- Proactive error detection
- Data masking before reporting
- Silent fallback for non-critical errors
- Hotfix distribution via Cloudflare

### Access Control
- PostgreSQL user management
- API authentication (Cloudflare Workers)
- Permission system for dashboard

---

## 2.7 Performance Optimization Strategy

### RoadRunner Optimizations
- Worker pool sizing (based on CPU cores)
- Max jobs per worker (prevent memory leaks)
- Persistent connections (database, cache)
- HTTP/2 multiplexing

### Spiral Optimizations
- OpCache for PHP bytecode
- Bootloader lazy loading
- Connection pooling
- Query optimization via Cycle ORM

### Database Optimizations
- PostgreSQL: JSONB indexing, GIN indexes
- SQLite: WAL mode, WITHOUT ROWID
- Query caching (APCu)
- Read replicas (SaaS mode)

### Frontend Optimizations
- SolidJS compiled to Vanilla JS
- IndexedDB caching
- Lazy loading images
- CSS/JS minification
- Brotli compression

---

## 2.8 PHP Coding Convention — No User Functions

> **Design Decision (cốt lõi)**: PrestoWorld **không sử dụng PHP user functions** (global
> functions) trong bất kỳ phần nào của Core. Toàn bộ logic được triển khai bằng
> **PSR-4 static methods**.

### Lý do

| Vấn đề | Giải thích |
|--------|------------|
| **Boot time** | PHP phải load **toàn bộ** file khai báo functions ngay khi `require`; kể cả hàm không bao giờ được gọi trong request đó |
| **OpCache** | File function lớn chiếm slot OpCache không cần thiết |
| **Autoload lazy** | PSR-4 class chỉ được autoload khi thực sự dùng → `spl_autoload_register` nạp đúng file, đúng lúc |
| **Treeshake** | Static method trên class riêng biệt → Composer autoload chỉ kéo file cần thiết |

### Quy tắc

```php
// ❌ KHÔNG làm — user function
function esc_html_native(string $str): string {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

// ✅ ĐÚNG — PSR-4 static method
namespace PrestoWorld\Core;

class Escape
{
    public static function html(string $str): string
    {
        return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
    }
}

// Gọi: Escape::html($str)
// Autoload: chỉ load khi Escape được dùng lần đầu
```

### Ngoại lệ duy nhất: WP Legacy Shim Layer

Shim functions (`add_action`, `esc_html`, `get_option`, …) trong `wp-compatibility.php`
**buộc phải là user functions** vì legacy plugin gọi chúng theo tên hàm toàn cục.
Tuy nhiên bản thân mỗi shim function chỉ là một **thin wrapper** gọi sang PSR-4 static
method — không chứa logic:

```php
// app/Core/Legacy/wp-compatibility.php
// (load on-demand bởi LegacyInvoker, không phải boot)

function esc_html(string $str): string {
    return \PrestoWorld\Core\Escape::html($str);
}

function add_action(string $tag, callable $cb, int $priority = 10, int $args = 1): void {
    \PrestoWorld\Core\Legacy\LegacyRegistry::recordHook('action', $tag, $cb, $priority, $args);
}
```

> File `wp-compatibility.php` **không được load tại boot time**.
> Nó chỉ được nạp bởi `LegacyInvoker` theo từng request liên quan đến legacy plugin
> (on-demand, lazy). Xem chi tiết: [06 §6.2](./06-legacy-support.md), [10.6](./10-wordpress-compiler.md).
