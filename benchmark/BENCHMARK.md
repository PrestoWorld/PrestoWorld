# Benchmark Suite

## Yêu cầu

- PHP 8.1+
- Extension `hrTime` (có sẵn trong PHP core từ 7.3+)

## Chạy toàn bộ benchmark

```bash
# Từ thư mục gốc của project
php benchmark/run.php
```

Kết quả hiển thị dạng bảng với các cột:

| Cột | Ý nghĩa |
|-----|---------|
| **Avg** | Thời gian trung bình mỗi lần lặp (µs) |
| **Total** | Tổng thời gian của tất cả iterations |
| **Throughput** | Số lần thực thi mỗi giây |
| **Iterations** | Số lần lặp để tính average |

## Lọc benchmark

Chạy một nhóm benchmark cụ thể:

```bash
# Theo tên class (không phân biệt hoa/thường)
php benchmark/run.php --filter=Router
php benchmark/run.php --filter=Container
php benchmark/run.php --filter=Boot
php benchmark/run.php --filter=Dashboard
```

## Cấu trúc thư mục

```
benchmark/
├── run.php                  # Runner script
├── framework/               # Benchmark cho Witals Framework
│   ├── Benchmark.php        # Base class (đo lường, format)
│   ├── BootBench.php        # Boot lifecycle, module discovery
│   ├── ContainerBench.php   # Container resolution, DI
│   └── RouterBench.php      # Route matching, dispatch, middleware
└── prestoworld/             # Benchmark cho PrestoWorld
    └── DashboardBench.php   # Admin dashboard, skin rendering, API
```

## Các benchmark hiện có

### `Benchmark\Framework\BootBench`

| Metric | Mô tả |
|--------|-------|
| `full_boot` | `new Application()` + `->boot()` — toàn bộ lifecycle |
| `constructor` | Chỉ `new Application()` — providers registration |
| `boot_only` | Chỉ `->boot()` — module loading, route registration |
| `module_discovery` | `ModuleManager::discover()` — scan modules |
| `memory_footprint` | Dung lượng memory sau boot |

### `Benchmark\Framework\RouterBench`

| Metric | Mô tả |
|--------|-------|
| `match_dashboard` | `RouteRegistry::match()` cho `/dashboard` |
| `match_api` | `match()` cho `/api/admin/menu` |
| `match_root_nomatch` | `match()` cho `/` (không match) |
| `dispatch_with_middleware` | `dispatch()` — match + middleware pipeline + runAction |
| `dispatch_nomatch` | `dispatch()` cho route không tồn tại |
| `full_handle` | `Application::handle()` — boot + dispatch + response |

### `Benchmark\Framework\ContainerBench`

| Metric | Mô tả |
|--------|-------|
| `make_singleton` | `make()` từ singleton binding (đã có instance) |
| `make_with_params` | `make()` với tham số (Request) |
| `make_response` | `make()` Response (ít params) |
| `call_closure` | `call()` Closure với DI |
| `call_method` | `call()` method của controller |
| `has_check` | `has()` interface check |
| `config_lookup` | `config('admin.skin')` dot-notation |

### `Benchmark\PrestoWorld\DashboardBench`

| Metric | Mô tả |
|--------|-------|
| `dispatch_render` | `RouteRegistry::dispatch()` qua middleware → SpaController |
| `controller_invoke` | `SpaController->__invoke()` thuần (không middleware) |
| `api_menu` | `SpaController->menu()` — JSON menu |
| `api_widgets` | `SpaController->dashboardWidgets()` — JSON widgets |
| `skin_render_layout` | `PrestoSpaSkin->renderLayout()` — HTML shell |
| `response_size` | Kích thước response (bytes) |
| `full_handle_dashboard` | Toàn bộ `Application::handle()` cho `/dashboard` |
| `middleware_overhead` | Giống full_handle nhưng đo middleware overhead |

## Giải thích kết quả

### Boot là bottleneck chính

```
full_boot:   457 µs
constructor: 126 µs
boot_only:   426 µs
```

`boot()` chiếm ~85% thời gian boot. Constructor tương đối nhẹ.

**Tối ưu:** Dùng RoadRunner hoặc FrankenPHP ở chế độ worker — chỉ boot 1 lần, tái sử dụng cho nhiều request. Throughput tăng từ ~2K/s lên ~46K/s (dispatch).

### Route matching là nhanh nhất

```
match /dashboard:  0.71 µs  (= 710 ns)
dispatch (có middleware):  22.71 µs
```

Matching gần như tức thời nhờ static index. Middleware pipeline là phần chậm nhất trong dispatch (~22 µs).

### Container operations đều sub-5µs

```
make singleton:  0.10 µs  (= 100 ns)
call closure:    0.94 µs
config lookup:   0.31 µs
```

`make()` với params (Request) chậm hơn (~2.87 µs) vì phải resolve dependencies từ container.

## Thêm benchmark mới

### 1. Tạo file PHP trong thư mục phù hợp

```php
<?php

namespace Benchmark\PrestoWorld;

use Benchmark\Framework\Benchmark as BaseBenchmark;

class MyFeatureBench extends BaseBenchmark
{
    private ?\App\Foundation\Application $app = null;

    public function run(): array
    {
        $this->ensureBooted();
        $results = [];

        $results['my_operation'] = $this->measure(
            fn() => /* operation cần đo */,
            1000  // iterations
        );

        return $results;
    }

    private function ensureBooted(): void
    {
        if ($this->app === null) {
            $this->app = new \App\Foundation\Application(getcwd(), \Witals\Framework\Contracts\RuntimeType::TRADITIONAL);
            $this->app->boot();
        }
    }
}
```

### 2. Chạy

```bash
php benchmark/run.php --filter=MyFeature
```

### Lưu ý

- **Warm-up:** Base class tự động chạy 10 lần warm-up trước khi đo
- **Closure capture:** Tránh capture biến phức tạp trong closure — dùng `use` hoặc property
- **Iterations:** Bắt đầu với 100 iterations, tăng dần nếu operation nhanh (> 10 µs có thể đo 5000+ iterations)
- **Memory:** `measureMemory()` đo delta memory — trả về `memory_delta_kb`
