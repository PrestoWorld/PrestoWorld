# CLI Command Reference

Trang này liệt kê tất cả các lệnh CLI có sẵn trong PrestoWorld. Chạy qua entry point `php presto` hoặc `php witals`.

---

## 1. Quản lý hệ thống

### `serve`
Khởi động application server.

```
php presto serve [--host=127.0.0.1] [--port=8000]
```

### `down`
Đưa ứng dụng vào chế độ bảo trì.

```
php presto down
```

### `up`
Thoát chế độ bảo trì.

```
php presto up
```

---

## 2. Cache

### `cache:clear`
Xoá toàn bộ cache framework (module discovery, theme.json, ...).

```
php presto cache:clear
```

### `config:cache`
Tạo file cache cho cấu hình, giúp tải nhanh hơn.

```
php presto config:cache
```

### `config:clear`
Xoá file cache cấu hình.

```
php presto config:clear
```

---

## 3. Database

### `db:verify`
Kiểm tra tính toàn vẹn của schema database — xác nhận tất cả bảng PrestoWorld cần thiết đều tồn tại.

```
php presto db:verify [-f|--force]
```

**Options:**
- `-f`, `--force` — Bỏ qua cache, chạy kiểm tra mới

### `db:copy`
Sao chép bảng PrestoWorld từ một database connection sang connection khác.

```
php presto db:copy <from> <to> [--prefix=wp_] [--drop]
```

**Arguments:**
- `from` — Tên connection nguồn (ví dụ: `wordpress`)
- `to` — Tên connection đích (ví dụ: `presto_pgsql`)

**Options:**
- `--prefix` — Table prefix WordPress (mặc định: `wp_`)
- `--drop` — Xoá bảng ở đích trước khi copy

### `db:seed`
Chạy seed database với dữ liệu mẫu.

```
php presto db:seed
```

### `seed`
Seed nội dung demo (pages, posts) kèm bản dịch EN + VI.

```
php presto seed
```

---

## 4. Schema & Migration

### `demo:schema`
Trình diễn Live Migration cho Taxonomies và Post Types.

```
php presto demo:schema
```

### `schema:sync`
Đồng bộ database schema từ các module.

```
php presto schema:sync
```

---

## 5. Queue

### `queue:work`
Xử lý jobs từ queue (daemon worker).

```
php presto queue:work [connection] [options]
```

**Arguments:**
- `connection` — Tên queue connection (mặc định: `default`)

**Options:**
- `--queue` — Queue để listen (mặc định: `default`)
- `--sleep` — Giây chờ khi không có job (mặc định: `3`)
- `--max-tries` — Số lần thử tối đa cho mỗi job (`0` = không giới hạn)
- `--backoff` — Giây chờ trước khi retry (mặc định: `0`)
- `--timeout` — Số giây tối đa cho một child process (mặc định: `60`)
- `--memory` — Giới hạn bộ nhớ (MB) (mặc định: `128`)
- `--rest` — Giây chờ trước khi restart worker (mặc định: `0`)

### `queue:failed`
Liệt kê tất cả failed jobs.

```
php presto queue:failed
```

### `queue:retry <id>`
Retry một failed job theo ID.

```
php presto queue:retry <id>
```

### `queue:forget <id>`
Xoá một failed job theo ID.

```
php presto queue:forget <id>
```

### `queue:flush`
Xoá tất cả failed jobs.

```
php presto queue:flush
```

---

## 6. Module

### `module:list`
Liệt kê tất cả modules đã được discover.

```
php presto module:list
```

### `module:validate`
Kiểm tra tất cả modules ở chế độ strict.

```
php presto module:validate
```

### `module:discover`
Discover và cache module routes để tối ưu hiệu năng.

```
php presto module:discover
```

### `make:module`
Tạo module mới với cấu trúc manifest.json.

```
php presto make:module <name> [--presto]
```

**Arguments:**
- `name` — Tên module (ví dụ: `Blog`)

**Options:**
- `--presto` — Tạo trong `framework/presto/modules/`
- (thiếu `--presto` sẽ tạo trong `framework/witals/modules/`)

### `make:block`
Tạo Gutenberg block renderer mới.

```
php presto make:block <name>
```

### `make:command`
Tạo console command mới.

```
php presto make:command <name>
```

### `make:provider`
Tạo service provider mới.

```
php presto make:provider <name>
```

---

## 7. PrestoWorld

### `presto:generate-stubs`
Tạo `wp-stubs.php` từ transformer definitions.

```
php presto presto:generate-stubs
```

File được tạo tại `framework/presto/modules/ClassicTheme/wp-stubs.php`.

---

## Ghi chú

- Dùng `php presto` cho hầu hết các lệnh.
- `migrate` command tồn tại (`App\Console\Commands\MigrateCommand`) nhưng **chưa được đăng ký** — không gọi được từ CLI.
- File `wp-stubs.php` giúp IDE hiểu được các WordPress function ảo khi làm việc với Classic Theme.
- Các lệnh `make:*` nhận argument là tên class (ví dụ: `php presto make:module Blog`).
