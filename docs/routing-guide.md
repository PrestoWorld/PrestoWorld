# Routing Guide

Hệ thống route được thiết kế linh hoạt hiệu suất cao, lấy cảm hứng từ Laravel, Symfony, FastRoute và các framework PHP lớn.

## Nội dung

- [Định nghĩa route](#định-nghĩa-route)
- [Dynamic parameters](#dynamic-parameters)
- [Named routes & URL generation](#named-routes--url-generation)
- [Action handlers](#action-handlers)
- [Route priorities](#route-priorities)
- [Bulk registration](#bulk-registration)
- [Performance](#performance)
- [API Reference](#api-reference)

---

## Định nghĩa route

### Quick syntax (qua Router facade)

```php
// routes/web.php
/** @var RouterInterface $router */

$router->get('/dashboard', [DashboardController::class, 'index']);
$router->post('/api/users', [UserController::class, 'store']);
$router->put('/api/users/{id}', [UserController::class, 'update']);
$router->delete('/api/users/{id}', [UserController::class, 'destroy']);
```

### Trực tiếp qua RouteRegistry (dùng cho module, hook, plugin)

```php
$registry->addRoute('GET', '/dashboard', $handler);
$registry->addRoute('POST', '/api/users', $handler, priority: 0);
$registry->addRoute('GET', '/archive/{year:\d{4}}/{month?}', $handler, priority: 1, options: ['name' => 'archive.show']);
```

### Bulk registration (batch array)

```php
$registry->addRoutes([
    ['method' => 'GET',  'path' => '/users',           'action' => $handler],
    ['method' => 'POST', 'path' => '/users',           'action' => $handler],
    ['method' => 'GET',  'path' => '/users/{id}',      'action' => $handler, 'options' => ['name' => 'users.show']],
    ['method' => 'GET',  'path' => '/users/create',    'action' => $handler],
    ['method' => 'GET',  'path' => '/posts/{slug}',    'action' => $handler, 'priority' => 0],
], priority: 1);
```

Mỗi route có thể override `priority` riêng, nếu không để trống thì dùng priority chung.

---

## Dynamic parameters

Hỗ trợ 3 dạng parameter giống Laravel:

### Required `{param}`

```php
$router->get('/users/{id}', $handler);
// /users/42 → ['id' => '42']
// /users/abc → ['id' => 'abc']
```

### Optional `{param?}`

Dấu `/` trước param tự động thành optional — không cần lo trailing slash:

```php
$router->get('/archive/{year}/{month?}', $handler);
// /archive/2026       → ['year' => '2026']
// /archive/2026/06    → ['year' => '2026', 'month' => '06']

$router->get('/category/{slug?}', $handler);
// /category           → []
// /category/tech      → ['slug' => 'tech']
```

### Regex constraint `{param:pattern}`

Constraint viết trực tiếp trong dấu `{}`, hỗ trợ nested braces `\d{4}`:

```php
$router->get('/user/{id:\d+}', $handler);
// /user/123   → match
// /user/abc   → not found

$router->get('/archive/{year:\d{4}}/{month?:\d{2}}', $handler);
// /archive/2026/06    → match
// /archive/2026      → match (month optional)
// /archive/abc       → not found

$router->get('/products/{category:[a-z-]+}/{id:\d{3,}}', $handler);
// /products/electronics/042   → match
// /products/123/042           → not found (category phải là chữ)

$router->get('/post/{slug:[a-z0-9-]+}', $handler);
// /post/hello-world   → match

$router->get('/locale/{locale:[a-z]{2}}', $handler);
// /locale/en  → match
// /locale/eng → not found

$router->get('/uuid/{id:[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}}', $handler);
// /uuid/550e8400-e29b-41d4-a716-446655440000   → match
```

### Kết hợp nhiều params

```php
$router->get('/api/v1/{resource}/{id:\d+}/relationships/{relation}', $handler);
// /api/v1/users/42/relationships/posts
// → ['resource' => 'users', 'id' => '42', 'relation' => 'posts']
```

---

## Named routes & URL generation

Đặt tên cho route qua `options`, sau đó sinh URL từ tên + params.

### Đặt tên

```php
$router->get('/articles/{id}', $handler);  // không tên — vẫn chạy

// Cách 1: options
$registry->addRoute('GET', '/articles/{id}', $handler, options: ['name' => 'article.show']);

// Cách 2: bulk
$registry->addRoutes([
    ['method' => 'GET', 'path' => '/articles/{id}', 'action' => $handler, 'options' => ['name' => 'article.show']],
]);
```

### Sinh URL

```php
$registry->url('article.show', ['id' => 42]);
// → '/articles/42'

$registry->url('article.show', ['id' => 999]);
// → '/articles/999'

// Optional params không được cung cấp → tự động bỏ
$registry->addRoute('GET', '/archive/{year}/{month?}', $handler, options: ['name' => 'archive.show']);
$registry->url('archive.show', ['year' => '2026']);
// → '/archive/2026'

$registry->url('archive.show', ['year' => '2026', 'month' => '06']);
// → '/archive/2026/06'
```

---

## Action handlers

Handler cực kỳ linh hoạt — thoải mái dùng kiểu nào cũng được.

### Closure

```php
$router->get('/health', function (Request $request): Response {
    return Response::json(['status' => 'ok']);
});

$router->get('/greet/{name}', function (string $name): string {
    return "Hello, $name!";
});
// Với Closure, tham số route được inject trực tiếp

$router->get('/user/{id}', function (int $id): string {
    return "User #$id";
});
// Có thể type hint để PHP cast tự động
```

### Controller method

```php
// String: __invoke controller
$router->get('/dashboard', SpaController::class);

// Array: [controller, method]
$router->get('/api/users', [UserController::class, 'index']);
$router->get('/api/users/{id}', [UserController::class, 'show']);
$router->post('/api/users', [UserController::class, 'store']);

// Controller được resolve từ container → có thể inject dependency
class UserController
{
    public function __construct(private UserRepository $users) {}

    public function show(string $id, Request $request): array
    {
        // Request cũng được inject tự động
        return ['user' => $this->users->find($id)];
    }
}
```

### Invokable class

```php
class HealthCheck
{
    public function __invoke(Request $request): Response
    {
        return Response::json(['status' => 'ok', 'time' => time()]);
    }
}

$router->get('/health', HealthCheck::class);
```

### Direct Response

```php
$router->get('/redirect', new RedirectResponse('/login'));
// Response object có thể dùng trực tiếp làm handler
```

### Return types — tự động convert

```php
// string → Response::html()
fn() => '<h1>Hello</h1>'

// array/object → Response::json()
fn() => ['user' => 'John']

// Response → giữ nguyên
fn() => Response::json(['ok' => true])
```

---

## Route priorities

5 priority levels, chạy từ cao xuống thấp. Route priority thấp hơn chỉ match nếu không có route nào ở priority cao hơn match.

| Constant | Value | Dùng cho |
|----------|-------|----------|
| `PRIORITY_MODULE` | 0 | Module routes (cao nhất, ghi đè mọi thứ) |
| `PRIORITY_NATIVE` | 1 (default) | `routes/web.php` |
| `PRIORITY_HOOK` | 2 | WordPress hooks (`admin_menu`, v.v.) |
| `PRIORITY_FALLBACK` | 3 | WordPress rewrite rules (thấp nhất) |

### Ví dụ

```php
// Module route — priority 0 (cao nhất)
$registry->addRoute('GET', '/dashboard', $moduleHandler, priority: 0);

// Native route — priority 1 (mặc định)
$router->get('/dashboard', $nativeHandler);

// Hook route — priority 2
$registry->addRoute('GET', '/dashboard', $hookHandler, priority: 2);

// Fallback — priority 3
$registry->addRoute('GET', '/dashboard', $fallbackHandler, priority: 3);

// Kết quả: /dashboard → moduleHandler (vì priority 0 thấp nhất)
```

### Dùng match() với maxPriority để giới hạn

```php
// Chỉ match route module (0) và native (1), bỏ qua hook và fallback
$match = $registry->match($request, maxPriority: RouteRegistryInterface::PRIORITY_NATIVE);
```

---

## Bulk registration

Thích hợp cho module manifest, config file, v.v.

```php
// Route config file (config/routes.php)
return [
    ['method' => 'GET',  'path' => '/posts',          'action' => [PostController::class, 'index']],
    ['method' => 'POST', 'path' => '/posts',          'action' => [PostController::class, 'store']],
    ['method' => 'GET',  'path' => '/posts/create',   'action' => [PostController::class, 'create']],
    ['method' => 'GET',  'path' => '/posts/{id}',     'action' => [PostController::class, 'show'], 'options' => ['name' => 'posts.show']],
    ['method' => 'GET',  'path' => '/posts/{id}/edit', 'action' => [PostController::class, 'edit']],
    ['method' => 'PUT',  'path' => '/posts/{id}',     'action' => [PostController::class, 'update']],
    ['method' => 'DELETE','path' => '/posts/{id}',    'action' => [PostController::class, 'destroy']],
];

// Load vào registry
$registry->addRoutes($routes, priority: 0);
```

---

## Performance

Route registry dùng **index-based matching** — không quét tuyến tính.

### Giải thuật

```
match(Request):
  for each priority (0 → 3):
    1. Static hashmap lookup → O(1)
    2. Dynamic prefix groups → O(G)
         str_starts_with(path, prefix) → preg_match(regex)
```

### Benchmark

| Metric | Value |
|--------|-------|
| Routes | 139 (100 static + 39 dynamic) |
| Iterations | 10,000 |
| Trung bình | **3.5 μs / match** |
| Throughput | **~284,000 req/s** |

Index được build 1 lần, cache pattern regex, static routes là hashmap O(1), dynamic routes được nhóm theo prefix — chỉ 1 preg_match chạy cho nhóm prefix match.

---

## API Reference

### `addRoute(string $method, string $path, mixed $action, int $priority, array $options): void`

Đăng ký 1 route.

| Param | Required | Mô tả |
|-------|----------|-------|
| `$method` | ✓ | `GET`, `POST`, `PUT`, `DELETE`, `PATCH`, v.v. (không phân biệt hoa thường) |
| `$path` | ✓ | `/users`, `/users/{id}`, `/archive/{year:\d{4}}/{month?}` |
| `$action` | ✓ | Closure, `[Controller::class, 'method']`, invokable class string, Response |
| `$priority` | ✗ | `0` (module) → `3` (fallback). Default: `1` (native) |
| `$options` | ✗ | `['name' => 'route.name']`, hoặc custom metadata |

### `addRoutes(array $routes, int $priority): void`

Bulk registration.

### `match(Request $request, ?int $maxPriority, ?string $path): ?array`

Match request, trả về `['action', 'params', 'priority', 'options', 'name']` hoặc `null`.

### `dispatch(Request $request): ?Response`

Match + run action, trả về Response hoặc null (nếu không match).

### `url(string $name, array $params): ?string`

Sinh URL từ named route.

### `getAll(): array`

Lấy tất cả routes đã đăng ký.

### `clear(): void`

Xoá toàn bộ routes. Dùng để reset trong test.

---

## Tóm tắt

```
┌─────────────────────────────────────────────────────────┐
│  Router facade (quick syntax)                           │
│    get(), post(), put(), delete()                       │
│    routes/web.php                                       │
├─────────────────────────────────────────────────────────┤
│  RouteRegistry (trực tiếp)                              │
│    addRoute(), addRoutes(), match(), dispatch()         │
│    Module, Hook, Plugin                                 │
├─────────────────────────────────────────────────────────┤
│  Dynamic params                                         │
│    {id} {slug?} {id:\d+} {year:\d{4}} {month?:\d{2}}   │
├─────────────────────────────────────────────────────────┤
│  Named routes + url()                                   │
│    options: ['name' => 'post.show']                     │
├─────────────────────────────────────────────────────────┤
│  Priority chain                                         │
│    Module(0) → Native(1) → Hook(2) → Fallback(3)       │
├─────────────────────────────────────────────────────────┤
│  Performance                                            │
│    O(1) static + O(G) prefix-grouped dynamic            │
│    ~284,000 req/s (139 routes)                          │
└─────────────────────────────────────────────────────────┘
```
