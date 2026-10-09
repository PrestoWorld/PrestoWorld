# 10. WordPress → PrestoWorld Compiler

## 10.1 Overview - Mô Hình 2 Chế Độ

**Objective**: Chuyển đổi mã nguồn WordPress (kỹ thuật lỗi thời) sang kiến trúc PrestoWorld.
Đây là một **compiler có 4 tầng transformer**: Query, WP-Function, Class, và User-Function.

**4 Lớp Transformer**:

| Layer | Tên | Nhiệm vụ | Chi tiết |
|-------|-----|----------|----------|
| 1 | **Query Transformer** | MySQL → PostgreSQL/SQLite, `$wpdb` → Cycle ORM | §10.3 |
| 2 | **WP Function Transformer** | ~390 hàm WP → PW services | §10.4 |
| 3 | **Class Transformer** | 50+ class WP → PW classes | §10.5 |
| 4 | **User Function Transformer** | User-defined functions → PSR-4 static methods | §10.6 |

> **Layer 4 — Triết lý cốt lõi**: Plugin/theme không được phép tồn tại với PHP user
> functions sau khi compile. Mọi `function my_helper() {}` trong plugin/theme đều bị
> rewrite thành `static method` trên PSR-4 class — đồng bộ với [02 §2.8](./02-architecture.md).

**2 Chế Độ Thực Thi**:

| Chế Độ | Khi nào | Cơ chế |
|--------|---------|--------|
| **Runtime Shim** | Plugin chạy ngay khi cài, không sửa mã nguồn | Define hàm/class WP trùng tên → delegate sang PW services |
| **Compile-time AST** | Chuyển đổi vĩnh viễn, plugin thành PW-native | `nikic/php-parser` rewrite source → emit package mới |

```
┌─────────────────── COMPILE-TIME (pw compile) ───────────────────┐
│  Plugin Source                                                   │
│      │                                                           │
│      ▼                                                           │
│  [Scan] → [Parse: AST] → [Analyze: symbol resolve]              │
│      │                                                           │
│      ▼                                                           │
│  [Pass 1: QueryTransformer]  → MySQL/wpdb → PG/Cycle            │
│  [Pass 2: WpFunctionTransformer] → ~390 WP fns → PW services    │
│  [Pass 3: ClassTransformer]  → WP_* classes → PW classes        │
│  [Pass 4: UserFunctionTransformer] → fn() {} → Class::method()  │
│      │                                                           │
│      ▼                                                           │
│  [Rewrite] → [Validate] → [Emit: compiled/] + [Report]           │
└──────────────────────────────────────────────────────────────────┘

┌──────────────────── RUNTIME (shim bridge) ───────────────────────┐
│  Legacy Plugin (unmodified)                                      │
│      │                                                           │
│      ▼                                                           │
│  LegacyInvoker (06 §6.4)                                         │
│      │                                                           │
│      ▼                                                           │
│  Shim Registry → PrestoWorld Services (Spiral IoC)               │
│      │                                                           │
│      ▼                                                           │
│  PrestoWpdb → QueryTransformer → PostgreSQL / SQLite             │
└──────────────────────────────────────────────────────────────────┘

        Cả hai chế độ đọc cùng một MASTER MAPPING REGISTRY
                        (§10.7 Translation Maps)
```

**Nguyên tắc**:
- **Bridge, Not a Wall**: Runtime shim là mặc định (migrate từ từ)
- **Compile là tối ưu**: Khi user sẵn sàng → `pw compile` để loại bỏ shim layer
- **No User Functions**: Compile-time xóa bỏ toàn bộ user-defined functions → static methods
- **Fallback an toàn**: Symbol không resolve được → giữ shim call + đánh dấu trong report

---

## 10.2 Compile-time Compiler Pipeline

### 10.2.1 Pipeline Stages

```
Stage 1: Scan       - Quét thư mục plugin, đếm files PHP, phát hiện entry point
Stage 2: Parse      - AST qua nikic/php-parser (PHP 8.3 target)
Stage 3: Analyze    - Symbol table: function/class nào PW đã có, nào cần shim;
                      phát hiện mọi user-defined functions → lập danh sách cho Pass 4
Stage 4: Transform  - 4 pass tuần tự:
                        Pass 1: QueryTransformer     (MySQL → PG/SQLite)
                        Pass 2: WpFunctionTransformer (~390 WP fns → PW services)
                        Pass 3: ClassTransformer      (WP_* → PW classes)
                        Pass 4: UserFunctionTransformer (user fn → static method)
Stage 5: Rewrite    - Ghi source mới với node đã transform
Stage 6: Validate   - Syntax check lại + runtime smoke test trong sandbox
Stage 7: Emit       - Package compiled/ + manifest JSON
```

### 10.2.2 CLI

```bash
# Compile một plugin
pw compile /path/to/plugin --out /path/to/compiled

# Compile với mapping version cụ thể
pw compile /path/to/plugin --map-version 2.1.0

# Chỉ phân tích, không ghi (dry run)
pw compile /path/to/plugin --analyze-only

# Output manifest
# {
#   "plugin": "contact-form-7",
#   "compiled_at": "2026-10-08T00:00:00Z",
#   "mapping_version": "2.1.0",
#   "stats": {
#     "queries": 14, "functions": 87, "classes": 12,
#     "user_functions_compiled": 23,    ← Pass 4: số user fn → static method
#     "user_functions_skipped": 1       ← fn không đủ điều kiện rewrite
#   },
#   "unresolved": [ "CF7_Custom_Send::send()" ],
#   "compatibility_score": 92,
#   "fallback_shims": [ "wp_editor" ]
# }
```

### 10.2.3 Pipeline Implementation (Spiral Command)

```php
namespace PrestoWorld\Core\Compiler;

use PhpParser\ParserFactory;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;

class WpCompiler
{
    private array $passes;

    public function __construct(
        private MappingRegistry $mappings,
        private SymbolAnalyzer $analyzer,
    ) {
        $this->passes = [
            new QueryPass($mappings),           // Pass 1: MySQL → PG/SQLite
            new WpFunctionPass($mappings),      // Pass 2: ~390 WP fns → PW services
            new ClassPass($mappings),           // Pass 3: WP_* classes → PW classes
            new UserFunctionPass(),             // Pass 4: user fn → static method
        ];
    }

    public function compile(string $sourceDir, string $outputDir, bool $dryRun = false): CompileReport
    {
        $files = (new PluginScanner())->scan($sourceDir);
        $parser = (new ParserFactory())->createForNewestSupportedVersion();
        $report = new CompileReport();

        foreach ($files as $file) {
            $ast = $parser->parse(file_get_contents($file));

            // Stage 3: Symbol resolve + collect user-defined functions
            $context = $this->analyzer->analyze($ast, $file);

            // Stage 4: Transform qua 4 pass
            $traverser = new NodeTraverser();
            $traverser->addVisitor(new NameResolver());
            foreach ($this->passes as $pass) {
                $pass->setContext($context, $report);
                $traverser->addVisitor($pass);
            }
            $newAst = $traverser->traverse($ast);

            if (!$dryRun) {
                // Stage 5-7: Rewrite + Emit
                $code = $this->printer->prettyPrintFile($newAst);
                $this->emit($outputDir, $file, $code);
            }
        }

        return $report;
    }
}
```

### 10.2.4 Failure Handling

| Tình huống | Xử lý |
|-----------|-------|
| Symbol không có trong mapping | Giữ nguyên call → gắn shim runtime; ghi vào `fallback_shims[]` |
| Class WP không có PW equivalent | Giữ FQN + autoload shim class (§10.5) |
| Query không parse được | Giữ nguyên string → chuyển sang `PrestoWpdb::query()` runtime transform |
| Syntax error sau rewrite | Abort file đó, giữ source gốc, đánh dấu `failed` trong report |
| Hàm `exit()`/`die()` | AST-rewrite thành `throw new LegacyTerminationException()` (an toàn với RoadRunner worker) |

### 10.2.5 Master Mapping Registry

```json
{
  "version": "2.1.0",
  "functions": { "esc_html": { "target": "PrestoWorld\\Core\\Escape::html", "mode": "sr" } },
  "classes":   { "WP_Query": { "target": "PrestoWorld\\Core\\Post\\PostQuery", "mode": "sr" } },
  "queries":   { "pattern": "/AUTO_INCREMENT/i", "replacement": "SERIAL", "target": "postgresql" }
}
```

- `mode`: `s` (shim only), `r` (rewrite only), `sr` (cả hai), `n` (noop), `x` (unsupported)
- Registry phân phối qua Cloudflare (§10.7), cache local trong `storage/framework/mappings/`

---

## 10.3 Layer 1: MySQL Query Transformer

> Runtime side: `PrestoWpdb` + `QueryTransformer` ở [05-database.md §5.4](./05-database.md).
> Section này là **canonical rule set** cho cả runtime lẫn compile-time Pass 1.

### 10.3.1 DDL Rules: MySQL → PostgreSQL

| MySQL | PostgreSQL | Ghi chú |
|-------|-----------|---------|
| `` `col` `` | `"col"` | Backtick → double quote |
| `AUTO_INCREMENT` | `SERIAL` | |
| `TINYINT(1)` | `BOOLEAN` | |
| `INT(N)` | `INTEGER` | Bỏ độ rộng |
| `BIGINT(N)` | `BIGINT` | |
| `UNSIGNED` | *(bỏ)* | PG không có |
| `BINARY` | *(bỏ)* | |
| `ENGINE=InnoDB` | *(bỏ)* | |
| `DEFAULT CHARSET=utf8mb4` | *(bỏ)* | |
| `ENUM('a','b')` | `VARCHAR(8) CHECK (col IN ('a','b'))` | |
| `SET('a','b')` | `TEXT[]` | |
| `DATETIME` | `TIMESTAMP` | |
| `LONGTEXT` | `TEXT` | |
| `MEDIUMBLOB` | `BYTEA` | |
| `CURRENT_TIMESTAMP` | `CURRENT_TIMESTAMP` | Giữ nguyên |
| `ON UPDATE CURRENT_TIMESTAMP` | Trigger / `updated_at` | PW dùng trigger |
| `FOREIGN KEY ... ON DELETE CASCADE` | Giữ nguyên | PG hỗ trợ đầy đủ |
| `IF NOT EXISTS` | Giữ nguyên | |
| `SHOW TABLES` | `SELECT tablename FROM pg_tables` | |
| `DESCRIBE table` | `information_schema.columns` | |

### 10.3.2 DDL Rules: MySQL → SQLite

| MySQL | SQLite | Ghi chú |
|-------|--------|---------|
| `` `col` `` | `"col"` | |
| `AUTO_INCREMENT` | `AUTOINCREMENT` | Chỉ với `INTEGER PRIMARY KEY` |
| `ENGINE=... CHARSET=...` | *(bỏ)* | |
| `UNSIGNED` | *(bỏ)* | |
| `ENUM(...)` | `TEXT CHECK (col IN (...))` | |
| `DATETIME`/`TIMESTAMP` | `TEXT` (ISO-8601) | |
| `TINYINT/SMALLINT/INT/BIGINT` | `INTEGER` | |
| `FOREIGN KEY ...` | Giữ nguyên *(không parse sâu)* | PRAGMA foreign_keys=ON |
| `ALTER TABLE ... ADD COLUMN` | Hạn chế → table rebuild | |
| `SHOW TABLES` | `sqlite_master` | |

### 10.3.3 DML / Function Rules (cả 2 target)

| MySQL | PostgreSQL | SQLite |
|-------|-----------|--------|
| `NOW()` | `CURRENT_TIMESTAMP` | `datetime('now')` |
| `IFNULL(a,b)` | `COALESCE(a,b)` | `COALESCE(a,b)` |
| `GROUP_CONCAT(x SEPARATOR ',')` | `STRING_AGG(x, ',')` | `GROUP_CONCAT(x, ',')` |
| `RAND()` | `RANDOM()` | `RANDOM()` |
| `FIND_IN_SET(a,b)` | `array_position(string_to_array(b,','), a)` | `INSTR(b, a) > 0` |
| `INSERT IGNORE INTO` | `ON CONFLICT DO NOTHING` | `INSERT OR IGNORE INTO` |
| `REPLACE INTO` | `INSERT ... ON CONFLICT ... DO UPDATE` | `INSERT OR REPLACE INTO` |
| `STRAIGHT_JOIN` | `INNER JOIN` | `INNER JOIN` |
| `LIMIT x, y` | `LIMIT y OFFSET x` | `LIMIT y OFFSET x` |
| `DATE_ADD(d, INTERVAL n DAY)` | `d + INTERVAL 'n day'` | `date(d, '+n days')` |
| `UNIX_TIMESTAMP()` | `EXTRACT(EPOCH FROM NOW())` | `strftime('%s','now')` |
| `CONCAT(a,b)` | `a \|\| b` | `a \|\| b` |
| `SUBSTRING_INDEX` | `split_part` | *(UDF / app-side)* |
| `LOCATE(a,b)` | `POSITION(a IN b)` | `INSTR(b, a)` |
| `SQL_CALC_FOUND_ROWS` | Subquery `COUNT(*)` | Subquery `COUNT(*)` |
| `FOUND_ROWS()` | `SELECT COUNT(*)` (cache) | `SELECT COUNT(*)` (cache) |
| `%s / %d / %f (prepare)` | `$1 / $2 ... (prepared)` | `?` placeholders |
| `'2024-01-01 00:00:00'` | Giữ nguyên | Giữ nguyên |

### 10.3.4 Table Mapping & Schema Translation

```
WordPress (MySQL)                    PrestoWorld
─────────────────                    ───────────
wp_users                       →     pw_users
wp_posts                       →     pw_posts (meta → JSONB, không tách bảng)
wp_postmeta                    →     pw_posts.meta (JSONB + GIN index)
wp_options                     →     pw_options
wp_comments                    →     pw_comments
wp_terms / wp_term_taxonomy    →     pw_terms (denormalized)
wp_termmeta                    →     pw_terms.meta
wp_links                       →     (bỏ - deprecated)
wp_termmeta                    →     pw_terms.meta
custom table (plugin)          →     SQLite sandbox (05 §5.3) hoặc pg_<name>
```

**Rule chuyển meta query**:
```sql
-- WordPress (MySQL)
SELECT * FROM wp_posts p
JOIN wp_postmeta m ON p.ID = m.post_id
WHERE m.meta_key = '_price' AND m.meta_value > 100;

-- PrestoWorld (PostgreSQL, Pass 1 rewrite)
SELECT * FROM pw_posts
WHERE (meta->>'_price')::numeric > 100;
```

### 10.3.5 `$wpdb` API → Cycle ORM Rewrite

| WordPress (compile-time input) | PrestoWorld (output) |
|-------------------------------|----------------------|
| `$wpdb->get_results("SELECT ...")` | `PostRepository::rawSelect(...)` / `DatabaseInterface::query()` |
| `$wpdb->get_row($sql)` | `$db->query($sql)->fetch()` |
| `$wpdb->get_var($sql)` | `$db->query($sql)->fetchColumn()` |
| `$wpdb->get_col($sql)` | `$db->query($sql)->fetchAll(PDO::FETCH_COLUMN)` |
| `$wpdb->query($sql)` | `$db->execute($sql)` |
| `$wpdb->prepare($sql, ...)` | `$db->query($sql, $params)` (parameterized, driver-native) |
| `$wpdb->insert($table, $data)` | Cycle ORM `Repository::create()` hoặc driver insert |
| `$wpdb->update($table, $data, $where)` | Cycle ORM `Repository::update()` |
| `$wpdb->delete($table, $where)` | Cycle ORM `Repository::delete()` |
| `$wpdb->prefix` | `$config->get('database.prefix')` → `'pw_'` |
| `$wpdb->insert_id` | `getLastInsertID()` |
| `$wpdb->num_rows` | `rowCount()` |
| `$wpdb->show_errors()` / `hide_errors()` | `QueryMonitor` config (05 §5.8) |
| `$wpdb->esc_like()` | `PrestoWpdb::escLike()` |
| `$wpdb->dbdelta()` | Migration system (05 §5.6) |

> **Runtime**: mọi method trên được shim trong `PrestoWpdb` (05 §5.1) và tự route qua `QueryTransformer`.
> **Compile-time**: Pass 1 chuyển thành lời gọi repository/driver trực tiếp → bỏ hẳn tầng `$wpdb`.

---

## 10.4 Layer 2: Function Transformer (~390 hàm)

### 10.4.1 Mapping Format

```json
{
  "source": "esc_html",
  "target": "PrestoWorld\\Core\\Escape::html",
  "mode": "sr",
  "signature_adapter": "static",
  "since": "1.0.0",
  "notes": "Một-động, giữ nguyên hành vi"
}
```

**Mode codes**: `s` = shim only · `r` = rewrite only · `sr` = cả hai · `n` = noop · `x` = unsupported

**Legend cột Target**: `Escape::`, `Format::`, `OptionRepository::`, `PostService::`... là các
service PW trong namespace `PrestoWorld\Core\` — resolve qua Spiral IoC.

---

### 10.4.2 Group 1: Escaping & Sanitization (27)

| Source | Target | Mode | Ghi chú |
|--------|--------|------|---------|
| `esc_html` | `Escape::html` | sr | |
| `esc_html__` | `Escape::html` + `Translator::__` | sr | |
| `esc_html_e` | `Escape::html` + `Translator::_e` | sr | |
| `esc_attr` | `Escape::attr` | sr | |
| `esc_attr__` | `Escape::attr` + `Translator::__` | sr | |
| `esc_attr_e` | `Escape::attr` + `Translator::_e` | sr | |
| `esc_url` | `Escape::url` | sr | |
| `esc_url_raw` | `Escape::urlRaw` | sr | |
| `esc_js` | `Escape::js` | sr | |
| `esc_textarea` | `Escape::textarea` | sr | |
| `esc_xml` | `Escape::xml` | sr | |
| `wp_kses` | `Kses::filter` | sr | Allow-list subset |
| `wp_kses_post` | `Kses::post` | sr | |
| `wp_kses_allowed_html` | `Kses::allowedHtml` | sr | |
| `sanitize_text_field` | `Sanitize::text` | sr | |
| `sanitize_textarea_field` | `Sanitize::textarea` | sr | |
| `sanitize_title` | `Sanitize::title` | sr | → slug |
| `sanitize_file_name` | `Sanitize::fileName` | sr | |
| `sanitize_email` | `Sanitize::email` | sr | |
| `sanitize_key` | `Sanitize::key` | sr | |
| `sanitize_user` | `Sanitize::user` | sr | |
| `sanitize_mime_type` | `Sanitize::mimeType` | sr | |
| `sanitize_html_class` | `Sanitize::htmlClass` | sr | |
| `wp_strip_all_tags` | `Sanitize::stripTags` | sr | |
| `wp_check_invalid_utf8` | `Sanitize::checkUtf8` | sr | |
| `_wp_specialchars` | `Escape::specialChars` | s | Internal, shim only |
| `absint` | `Sanitize::absInt` | sr | |

### 10.4.3 Group 2: Security & Nonces (13)

| Source | Target | Mode | Ghi chú |
|--------|--------|------|---------|
| `wp_create_nonce` | `Nonce::create` | sr | |
| `wp_verify_nonce` | `Nonce::verify` | sr | |
| `wp_nonce_field` | `Nonce::field` | sr | |
| `wp_nonce_url` | `Nonce::url` | sr | |
| `wp_referer_field` | `Security::refererField` | sr | |
| `check_admin_referer` | `Nonce::checkAdmin` | sr | |
| `check_ajax_referer` | `Nonce::checkAjax` | sr | |
| `wp_get_referer` | `Request::referer` | sr | |
| `auth_redirect` | `AuthService::requireLogin` | sr | |
| `wp_set_auth_cookie` | `AuthService::setCookie` | sr | |
| `wp_clear_auth_cookie` | `AuthService::clearCookie` | sr | |
| `wp_parse_auth_cookie` | `AuthService::parseCookie` | s | |
| `wp_validate_auth_cookie` | `AuthService::validateCookie` | sr | |

### 10.4.4 Group 3: Formatting, Text & Dates (32)

| Source | Target | Mode | Ghi chú |
|--------|--------|------|---------|
| `number_format_i18n` | `Format::number` | sr | |
| `date_i18n` | `Format::date` | sr | |
| `mysql2date` | `Format::mysqlDate` | sr | |
| `human_time_diff` | `Format::humanTimeDiff` | sr | |
| `get_gmt_from_date` | `Format::toGmt` | sr | |
| `get_date_from_gmt` | `Format::fromGmt` | sr | |
| `current_time` | `Clock::current` | sr | |
| `wp_date` | `Clock::date` | sr | |
| `current_datetime` | `Clock::now` | sr | |
| `wp_timezone` | `Clock::timezone` | sr | |
| `wp_timezone_offset` | `Clock::offset` | sr | |
| `size_format` | `Format::size` | sr | |
| `wp_html_excerpt` | `Format::htmlExcerpt` | sr | |
| `wp_trim_words` | `Format::trimWords` | sr | |
| `trailingslashit` | `Format::trailingslash` | sr | |
| `untrailingslashit` | `Format::untrailingslash` | sr | |
| `wp_basename` | `Format::basename` | sr | |
| `wp_normalize_path` | `Format::normalizePath` | sr | |
| `path_join` | `Format::pathJoin` | sr | |
| `wp_json_encode` | `json_encode` (rewrite) | r | |
| `maybe_serialize` | `Format::serialize` | sr | Vẫn serialize mảng như WP |
| `maybe_unserialize` | `Format::unserialize` | sr | |
| `is_serialized` | `Format::isSerialized` | sr | |
| `wp_rand` | `Security::rand` | sr | CSPRNG |
| `wp_generate_password` | `Security::generatePassword` | sr | |
| `strip_shortcodes` | `Shortcode::strip` | sr | |
| `do_shortcode` | `Shortcode::render` | sr | |
| `has_shortcode` | `Shortcode::has` | sr | |
| `shortcode_atts` | `Shortcode::atts` | sr | |
| `wptexturize` | `Format::texturize` | n | PW render HTML sạch → noop |
| `wpautop` | `Format::autop` | sr | |
| `convert_smilies` | `Format::smilies` | n | |

### 10.4.5 Group 4: Options & Transients (20)

| Source | Target | Mode | Ghi chú |
|--------|--------|------|---------|
| `get_option` | `OptionRepository::get` | sr | Cache trong RAM (RoadRunner) |
| `update_option` | `OptionRepository::update` | sr | |
| `add_option` | `OptionRepository::add` | sr | |
| `delete_option` | `OptionRepository::delete` | sr | |
| `wp_load_alloptions` | `OptionRepository::loadAll` | sr | |
| `set_transient` | `CacheRepository::set` | sr | |
| `get_transient` | `CacheRepository::get` | sr | |
| `delete_transient` | `CacheRepository::delete` | sr | |
| `set_site_transient` | `CacheRepository::set` | s | Không có network |
| `get_site_transient` | `CacheRepository::get` | s | |
| `delete_site_transient` | `CacheRepository::delete` | s | |
| `get_site_option` | `OptionRepository::getNetwork` | s | |
| `update_site_option` | `OptionRepository::updateNetwork` | s | |
| `delete_site_option` | `OptionRepository::deleteNetwork` | s | |
| `wp_cache_get` | `CacheRepository::get` | sr | |
| `wp_cache_set` | `CacheRepository::set` | sr | |
| `wp_cache_delete` | `CacheRepository::delete` | sr | |
| `wp_cache_flush` | `CacheRepository::flush` | sr | |
| `wp_cache_incr` | `CacheRepository::increment` | sr | |
| `wp_using_ext_object_cache` | `CacheRepository::usingExternal` | sr | |

### 10.4.6 Group 5: Posts & Content (35)

| Source | Target | Mode | Ghi chú |
|--------|--------|------|---------|
| `get_post` | `PostRepository::find` | sr | |
| `get_posts` | `PostRepository::query` | sr | |
| `query_posts` | `PostRepository::query` | r | Deprecated cả trong WP |
| `the_post` | `PostLoop::thePost` | sr | |
| `the_content` | `PostView::content` | sr | |
| `the_title` | `PostView::title` | sr | |
| `the_excerpt` | `PostView::excerpt` | sr | |
| `get_the_content` | `PostView::getContent` | sr | |
| `get_the_title` | `PostView::getTitle` | sr | |
| `get_the_excerpt` | `PostView::getExcerpt` | sr | |
| `get_post_field` | `PostRepository::field` | sr | |
| `get_post_status` | `PostRepository::status` | sr | |
| `get_post_type` | `PostRepository::type` | sr | |
| `get_post_types` | `PostTypeRegistry::all` | sr | |
| `get_post_type_object` | `PostTypeRegistry::get` | sr | |
| `register_post_type` | `PostTypeRegistry::register` | sr | |
| `wp_insert_post` | `PostService::create` | sr | |
| `wp_update_post` | `PostService::update` | sr | |
| `wp_delete_post` | `PostService::delete` | sr | |
| `wp_publish_post` | `PostService::publish` | sr | |
| `wp_trash_post` | `PostService::trash` | sr | |
| `wp_untrash_post` | `PostService::untrash` | sr | |
| `sanitize_post` | `PostService::sanitize` | sr | |
| `setup_postdata` | `PostLoop::setup` | sr | |
| `have_posts` | `PostLoop::havePosts` | sr | |
| `the_ID` | `PostView::id` | sr | |
| `get_the_ID` | `PostView::getId` | sr | |
| `get_the_author` | `PostView::author` | sr | |
| `get_avatar` | `MediaService::avatar` | sr | |
| `get_avatar_url` | `MediaService::avatarUrl` | sr | |
| `wp_trim_excerpt` | `Format::trimExcerpt` | sr | |
| `get_pages` | `PostRepository::pages` | sr | |
| `get_next_post` | `PostRepository::next` | sr | |
| `get_previous_post` | `PostRepository::previous` | sr | |
| `wp_get_post_revisions` | `PostRepository::revisions` | sr | |

### 10.4.7 Group 6: Users & Capabilities (22)

| Source | Target | Mode | Ghi chú |
|--------|--------|------|---------|
| `is_user_logged_in` | `AuthService::isLoggedIn` | sr | |
| `wp_get_current_user` | `AuthService::currentUser` | sr | |
| `wp_set_current_user` | `AuthService::setCurrentUser` | sr | |
| `get_current_user_id` | `AuthService::currentUserId` | sr | |
| `current_user_can` | `CapabilityService::can` | sr | |
| `current_user_can_for_blog` | `CapabilityService::can` | s | Đơn tenant |
| `user_can` | `CapabilityService::userCan` | sr | |
| `get_userdata` | `UserRepository::find` | sr | |
| `get_user_by` | `UserRepository::findBy` | sr | |
| `get_users` | `UserRepository::query` | sr | |
| `wp_create_user` | `UserService::create` | sr | |
| `wp_insert_user` | `UserService::insert` | sr | |
| `wp_update_user` | `UserService::update` | sr | |
| `wp_delete_user` | `UserService::delete` | sr | |
| `wp_signon` | `AuthService::signIn` | sr | |
| `wp_logout` | `AuthService::signOut` | sr | |
| `wp_authenticate` | `AuthService::authenticate` | sr | |
| `get_role` | `RoleRegistry::get` | sr | |
| `add_role` | `RoleRegistry::add` | sr | |
| `remove_role` | `RoleRegistry::remove` | sr | |
| `wp_roles` | `RoleRegistry::instance` | sr | |
| `is_super_admin` | `CapabilityService::isSuperAdmin` | sr | |

### 10.4.8 Group 7: URLs & Redirects (28)

| Source | Target | Mode | Ghi chú |
|--------|--------|------|---------|
| `home_url` | `Url::home` | sr | |
| `site_url` | `Url::site` | sr | |
| `admin_url` | `Url::admin` | sr | |
| `self_admin_url` | `Url::selfAdmin` | sr | |
| `content_url` | `Url::content` | sr | |
| `includes_url` | `Url::includes` | sr | |
| `get_home_path` | `Path::home` | sr | |
| `get_permalink` | `Url::permalink` | sr | |
| `get_attachment_link` | `Url::attachment` | sr | |
| `get_author_posts_url` | `Url::authorArchive` | sr | |
| `get_category_link` | `Url::category` | sr | |
| `get_tag_link` | `Url::tag` | sr | |
| `get_term_link` | `Url::term` | sr | |
| `get_search_link` | `Url::search` | sr | |
| `get_post_type_archive_link` | `Url::postTypeArchive` | sr | |
| `wp_login_url` | `Url::login` | sr | |
| `wp_logout_url` | `Url::logout` | sr | |
| `wp_registration_url` | `Url::register` | sr | |
| `wp_lostpassword_url` | `Url::lostPassword` | sr | |
| `set_url_scheme` | `Url::setScheme` | sr | |
| `wp_redirect` | `RedirectService::to` | sr | |
| `wp_safe_redirect` | `RedirectService::safe` | sr | |
| `wp_sanitize_redirect` | `RedirectService::sanitize` | sr | |
| `nocache_headers` | `Response::noCache` | sr | |
| `status_header` | `Response::status` | sr | |
| `add_query_arg` | `Url::addQueryArg` | sr | |
| `remove_query_arg` | `Url::removeQueryArg` | sr | |
| `get_query_arg` | `Url::getQueryArg` | sr | |

### 10.4.9 Group 8: Conditional Tags & Template (21)

| Source | Target | Mode | Ghi chú |
|--------|--------|------|---------|
| `is_admin` | `Request::isAdmin` | sr | PW có shadow wp-admin (08 §8.8) |
| `is_front_page` | `QueryState::isFrontPage` | sr | |
| `is_home` | `QueryState::isHome` | sr | |
| `is_single` | `QueryState::isSingle` | sr | |
| `is_page` | `QueryState::isPage` | sr | |
| `is_singular` | `QueryState::isSingular` | sr | |
| `is_attachment` | `QueryState::isAttachment` | sr | |
| `is_sticky` | `QueryState::isSticky` | sr | |
| `is_archive` | `QueryState::isArchive` | sr | |
| `is_category` | `QueryState::isCategory` | sr | |
| `is_tag` | `QueryState::isTag` | sr | |
| `is_tax` | `QueryState::isTax` | sr | |
| `is_author` | `QueryState::isAuthor` | sr | |
| `is_date` | `QueryState::isDate` | sr | |
| `is_404` | `QueryState::is404` | sr | |
| `is_search` | `QueryState::isSearch` | sr | |
| `is_feed` | `QueryState::isFeed` | n | |
| `is_paged` | `QueryState::isPaged` | sr | |
| `is_main_query` | `QueryState::isMainQuery` | sr | |
| `is_customize_preview` | `QueryState::isCustomizePreview` | n | |
| `get_bloginfo` | `SiteInfo::get` | sr | |

### 10.4.10 Group 9: i18n (18)

| Source | Target | Mode | Ghi chú |
|--------|--------|------|---------|
| `__` | `Translator::__` | sr | |
| `_e` | `Translator::_e` | sr | |
| `_x` | `Translator::_x` | sr | |
| `_ex` | `Translator::_ex` | sr | |
| `_n` | `Translator::_n` | sr | |
| `_nx` | `Translator::_nx` | sr | |
| `_n_noop` | `Translator::_nNoop` | sr | |
| `_nx_noop` | `Translator::_nxNoop` | sr | |
| `translate` | `Translator::translate` | sr | |
| `translate_with_gettext_context` | `Translator::withContext` | sr | |
| `load_plugin_textdomain` | `Translator::loadPluginDomain` | sr | |
| `load_theme_textdomain` | `Translator::loadThemeDomain` | sr | |
| `load_textdomain` | `Translator::load` | sr | |
| `unload_textdomain` | `Translator::unload` | sr | |
| `is_textdomain_loaded` | `Translator::isLoaded` | sr | |
| `dgettext` | `Translator::dget` | sr | |
| `dngettext` | `Translator::dnget` | sr | |
| `determine_locale` | `Translator::locale` | sr | |

### 10.4.11 Group 10: Hooks (18) - cross-ref [06 §6.2](./06-legacy-support.md)

| Source | Target | Mode | Ghi chú |
|--------|--------|------|---------|
| `add_action` | `LegacyRegistry::recordHook` | s | Chi tiết 06 §6.2.1 |
| `add_filter` | `LegacyRegistry::recordHook` | s | |
| `remove_action` | `LegacyRegistry::removeHook` | s | |
| `remove_filter` | `LegacyRegistry::removeHook` | s | |
| `remove_all_actions` | `LegacyRegistry::removeAll` | s | |
| `remove_all_filters` | `LegacyRegistry::removeAll` | s | |
| `do_action` | `LegacyInvoker::trigger` | s | |
| `do_action_ref_array` | `LegacyInvoker::trigger` | s | |
| `apply_filters` | `LegacyInvoker::trigger` | s | |
| `apply_filters_ref_array` | `LegacyInvoker::trigger` | s | |
| `doing_action` | `LegacyInvoker::doing` | s | |
| `doing_filter` | `LegacyInvoker::doing` | s | |
| `did_action` | `LegacyInvoker::did` | s | |
| `has_action` | `LegacyRegistry::has` | s | |
| `has_filter` | `LegacyRegistry::has` | s | |
| `current_filter` | `LegacyInvoker::current` | s | |
| `current_action` | `LegacyInvoker::current` | s | |
| `default_filters` | *(bỏ)* | n | PW tự bootstrap hooks |

> Compile-time: hook đăng ký trong plugin được **static-collect** vào
> `presto_registry.php` (02 §2.5) thay vì ghi SQLite khi runtime.

### 10.4.12 Group 11: Assets (16)

| Source | Target | Mode | Ghi chú |
|--------|--------|------|---------|
| `wp_enqueue_script` | `AssetManager::enqueueScript` | sr | |
| `wp_register_script` | `AssetManager::registerScript` | sr | |
| `wp_dequeue_script` | `AssetManager::dequeueScript` | sr | |
| `wp_deregister_script` | `AssetManager::deregisterScript` | sr | |
| `wp_enqueue_style` | `AssetManager::enqueueStyle` | sr | |
| `wp_register_style` | `AssetManager::registerStyle` | sr | |
| `wp_dequeue_style` | `AssetManager::dequeueStyle` | sr | |
| `wp_deregister_style` | `AssetManager::deregisterStyle` | sr | |
| `wp_script_is` | `AssetManager::scriptIs` | sr | |
| `wp_style_is` | `AssetManager::styleIs` | sr | |
| `wp_localize_script` | `AssetManager::localize` | sr | |
| `wp_add_inline_script` | `AssetManager::addInlineScript` | sr | |
| `wp_add_inline_style` | `AssetManager::addInlineStyle` | sr | |
| `wp_enqueue_block_style` | `AssetManager::enqueueStyle` | r | PW không có block system |
| `wp_register_script_module` | `AssetManager::registerModule` | sr | |
| `wp_default_scripts` | *(bỏ)* | n | Core assets tự register |

### 10.4.13 Group 12: Theme (14)

| Source | Target | Mode | Ghi chú |
|--------|--------|------|---------|
| `get_template_directory` | `ThemeManager::directory` | sr | |
| `get_stylesheet_directory` | `ThemeManager::stylesheetDirectory` | sr | |
| `get_template_directory_uri` | `ThemeManager::directoryUri` | sr | |
| `get_stylesheet_directory_uri` | `ThemeManager::stylesheetUri` | sr | |
| `get_stylesheet` | `ThemeManager::stylesheet` | sr | |
| `get_template` | `ThemeManager::template` | sr | |
| `template_uri` | `ThemeManager::templateUri` | sr | |
| `wp_get_theme` | `ThemeManager::get` | sr | |
| `locate_template` | `ThemeManager::locate` | sr | |
| `load_template` | `ThemeManager::load` | sr | |
| `get_header` | `ThemeManager::header` | sr | FSE template part |
| `get_footer` | `ThemeManager::footer` | sr | |
| `get_sidebar` | `ThemeManager::sidebar` | sr | |
| `get_template_part` | `ThemeManager::templatePart` | sr | |

### 10.4.14 Group 13: Files & Uploads (15)

| Source | Target | Mode | Ghi chú |
|--------|--------|------|---------|
| `wp_upload_dir` | `UploadService::dir` | sr | S3/R2-native (07) |
| `wp_upload_bits` | `UploadService::bits` | sr | |
| `wp_handle_upload` | `UploadService::handle` | sr | |
| `wp_handle_sideload` | `UploadService::sideload` | sr | |
| `wp_check_filetype` | `UploadService::checkFileType` | sr | |
| `wp_check_filetype_and_ext` | `UploadService::checkFiletypeExt` | sr | |
| `wp_unique_filename` | `UploadService::uniqueFilename` | sr | |
| `wp_mkdir_p` | `Filesystem::mkdirP` | sr | |
| `download_url` | `HttpService::download` | sr | |
| `wp_tempnam` | `Filesystem::tempName` | sr | |
| `copy_dir` | `Filesystem::copyDir` | sr | |
| `move_dir` | `Filesystem::moveDir` | sr | |
| `wp_is_writable` | `Filesystem::isWritable` | sr | |
| `wp_is_file_mod_allowed` | `Filesystem::modAllowed` | sr | |
| `validate_file` | `Filesystem::validate` | sr | |

### 10.4.15 Group 14: Media / Attachments (13)

| Source | Target | Mode | Ghi chú |
|--------|--------|------|---------|
| `get_attached_file` | `MediaService::file` | sr | |
| `update_attached_file` | `MediaService::updateFile` | sr | |
| `wp_get_attachment_url` | `MediaService::url` | sr | |
| `wp_get_attachment_metadata` | `MediaService::metadata` | sr | |
| `wp_update_attachment_metadata` | `MediaService::updateMetadata` | sr | |
| `wp_generate_attachment_metadata` | `MediaService::generateMetadata` | sr | |
| `wp_insert_attachment` | `MediaService::insert` | sr | |
| `wp_delete_attachment` | `MediaService::delete` | sr | |
| `wp_get_attachment_image` | `MediaService::image` | sr | |
| `wp_get_attachment_image_src` | `MediaService::imageSrc` | sr | |
| `wp_create_image_subsizes` | `MediaService::subsizes` | sr | |
| `wp_crop_image` | `MediaService::crop` | sr | |
| `wp_get_attachment_caption` | `MediaService::caption` | sr | |

### 10.4.16 Group 15: Meta & Taxonomy (36)

| Source | Target | Mode | Ghi chú |
|--------|--------|------|---------|
| `get_post_meta` | `MetaRepository::get` | sr | → JSONB `meta->>` |
| `add_post_meta` | `MetaRepository::add` | sr | |
| `update_post_meta` | `MetaRepository::update` | sr | |
| `delete_post_meta` | `MetaRepository::delete` | sr | |
| `get_post_custom` | `MetaRepository::all` | sr | |
| `get_post_custom_keys` | `MetaRepository::keys` | sr | |
| `get_post_custom_values` | `MetaRepository::values` | sr | |
| `register_post_meta` | `MetaRepository::register` | sr | |
| `register_meta` | `MetaRepository::register` | sr | |
| `get_metadata` | `MetaRepository::getGeneric` | sr | |
| `update_metadata` | `MetaRepository::updateGeneric` | sr | |
| `delete_metadata` | `MetaRepository::deleteGeneric` | sr | |
| `metadata_exists` | `MetaRepository::exists` | sr | |
| `wp_get_object_terms` | `TermRepository::objectTerms` | sr | |
| `wp_set_object_terms` | `TermRepository::setObjectTerms` | sr | |
| `wp_set_post_terms` | `TermRepository::setPostTerms` | sr | |
| `wp_get_post_terms` | `TermRepository::getPostTerms` | sr | |
| `wp_remove_object_terms` | `TermRepository::removeObjectTerms` | sr | |
| `get_terms` | `TermRepository::query` | sr | |
| `get_term` | `TermRepository::find` | sr | |
| `get_term_children` | `TermRepository::children` | sr | |
| `get_category` | `TermRepository::category` | sr | |
| `get_categories` | `TermRepository::categories` | sr | |
| `get_tags` | `TermRepository::tags` | sr | |
| `term_exists` | `TermRepository::exists` | sr | |
| `wp_insert_term` | `TermService::create` | sr | |
| `wp_update_term` | `TermService::update` | sr | |
| `wp_delete_term` | `TermService::delete` | sr | |
| `wp_delete_category` | `TermService::deleteCategory` | sr | |
| `register_taxonomy` | `TaxonomyRegistry::register` | sr | |
| `register_taxonomy_for_object_type` | `TaxonomyRegistry::forObjectType` | sr | |
| `get_taxonomies` | `TaxonomyRegistry::all` | sr | |
| `get_taxonomy` | `TaxonomyRegistry::get` | sr | |
| `is_object_in_taxonomy` | `TaxonomyRegistry::has` | sr | |
| `get_the_terms` | `TermRepository::theTerms` | sr | |
| `has_term` / `is_object_in_term` | `TermRepository::hasTerm` | sr | Gộp 2 → 1 |

### 10.4.17 Group 16: Comments (17)

| Source | Target | Mode | Ghi chú |
|--------|--------|------|---------|
| `get_comments` | `CommentRepository::query` | sr | |
| `get_comment` | `CommentRepository::find` | sr | |
| `wp_get_comments` | `CommentRepository::query` | sr | |
| `wp_insert_comment` | `CommentService::create` | sr | |
| `wp_update_comment` | `CommentService::update` | sr | |
| `wp_delete_comment` | `CommentService::delete` | sr | |
| `wp_get_comment_status` | `CommentService::status` | sr | |
| `wp_set_comment_status` | `CommentService::setStatus` | sr | |
| `wp_allow_comment` | `CommentService::allow` | sr | |
| `check_comment` | `CommentService::check` | sr | |
| `wp_new_comment` | `CommentService::insert` | sr | |
| `comment_form` | `CommentView::form` | sr | |
| `wp_list_comments` | `CommentView::list` | sr | |
| `get_comment_author` | `CommentView::author` | sr | |
| `get_comment_text` | `CommentView::text` | sr | |
| `wp_count_comments` | `CommentRepository::count` | sr | |
| `wp_get_comment_timestamp` | `CommentRepository::timestamp` | sr | |

### 10.4.18 Group 17: AJAX, REST & Cron (20)

| Source | Target | Mode | Ghi chú |
|--------|--------|------|---------|
| `wp_send_json` | `JsonResponse::send` | sr | |
| `wp_send_json_success` | `JsonResponse::success` | sr | |
| `wp_send_json_error` | `JsonResponse::error` | sr | |
| `wp_doing_ajax` | `Request::doingAjax` | sr | |
| `wp_doing_cron` | `Request::doingCron` | sr | |
| `register_rest_route` | `RestController::register` | sr | |
| `rest_ensure_response` | `RestController::ensureResponse` | sr | |
| `rest_get_server` | `RestController::server` | sr | |
| `rest_do_request` | `RestController::dispatch` | sr | |
| `rest_filter_response_by_context` | `RestController::filterContext` | sr | |
| `wp_schedule_single_event` | `Scheduler::once` | sr | Spiral Queue |
| `wp_schedule_event` | `Scheduler::recurring` | sr | |
| `wp_schedule_recurring_event` | `Scheduler::recurring` | sr | |
| `wp_clear_scheduled_hook` | `Scheduler::clear` | sr | |
| `wp_unschedule_event` | `Scheduler::unschedule` | sr | |
| `wp_next_scheduled` | `Scheduler::next` | sr | |
| `wp_get_scheduled_event` | `Scheduler::event` | sr | |
| `wp_get_schedules` | `Scheduler::schedules` | sr | |
| `wp_cron` | `Scheduler::run` | sr | |
| `spawn_cron` | `Scheduler::spawn` | sr | |

### 10.4.19 Group 18: Misc & Pluggable (27)

| Source | Target | Mode | Ghi chú |
|--------|--------|------|---------|
| `wp_die` | `throw LegacyTerminationException` | sr | **Bắt buộc** với RoadRunner |
| `wp_slash` | `Sanitize::slash` | sr | |
| `wp_unslash` | `Sanitize::unslash` | sr | |
| `stripslashes_deep` | `Sanitize::stripslashesDeep` | sr | |
| `wp_magic_quotes` | *(bỏ)* | n | |
| `wp_mail` | `MailService::send` | sr | (07 - Cloudflare Email) |
| `wp_installing` | `ApplicationState::installing` | sr | |
| `wp_get_environment_type` | `AppInfo::environment` | sr | |
| `wp_raise_memory_limit` | `AppInfo::raiseMemory` | n | Worker pool tự quản lý |
| `wp_suspend_cache_addition` | `CacheRepository::suspend` | sr | |
| `wp_get_current_screen` | `ScreenRegistry::current` | sr | |
| `get_current_screen` | `ScreenRegistry::current` | sr | |
| `add_meta_box` | `MetaBoxRegistry::add` | sr | |
| `remove_meta_box` | `MetaBoxRegistry::remove` | sr | |
| `do_meta_boxes` | `MetaBoxRegistry::render` | sr | |
| `add_screen_option` | `ScreenRegistry::addOption` | sr | |
| `plugin_basename` | `PluginInfo::basename` | s | |
| `plugin_dir_path` | `PluginInfo::path` | s | |
| `plugin_dir_url` | `PluginInfo::url` | s | |
| `is_plugin_active` | `PluginState::isActive` | s | |
| `is_plugin_inactive` | `PluginState::isInactive` | s | |
| `activate_plugin` | `PluginActivator::activate` | s | 06 §6.5 |
| `deactivate_plugin` | `PluginActivator::deactivate` | s | |
| `deactivate_plugins` | `PluginActivator::deactivateMany` | s | |
| `register_activation_hook` | `PluginActivator::onActivation` | s | |
| `register_deactivation_hook` | `PluginActivator::onDeactivation` | s | |
| `register_uninstall_hook` | `PluginActivator::onUninstall` | s | |

**Unsupported / Deprecated (mode `x`)**:

| Source | Lý do | Thay thế |
|--------|-------|----------|
| `mysql_connect`, `mysqli_*` | PW không expose MySQL socket trực tiếp | `PrestoWpdb` |
| `get_currentuserinfo` | Deprecated từ WP 4.5 | `wp_get_current_user` |
| `convert_chars` | Không còn cần (UTF-8 everywhere) | — |
| `wp_get_sites` | Multisite-only | — |
| `switch_to_blog` | Đơn tenant | `TenantContext` (SaaS) |
| `is_multisite` | Đơn tenant | `false` (shim trả `false`) |
| `wpmu_*`, `ms_.*` | Multisite functions | Unsupported |
| `wpdb::get_blog_prefix` | Đơn tenant | `getPrefix()` |

**Tổng**: ~390 hàm được map (18 nhóm). Chưa kể 8 hàm
deprecated (`mysql_*`, `create_function`, ...) được treo ở mục phía dưới.

---

## 10.5 Layer 3: Class Transformer (55+ class)

### 10.5.1 Mapping Format

```json
{
  "source": "WP_Query",
  "target": "PrestoWorld\\Core\\Post\\PostQuery",
  "mode": "sr",
  "structural": {
    "constructor": "ioc",
    "static_methods": "instance",
    "global_state": "scoped"
  }
}
```

### 10.5.2 3 Cơ Chế Chuyển Đổi

| Cơ chế | Áp dụng | Mô tả |
|--------|---------|-------|
| **Shim class** | Class đơn giản, API tương đương | Tự autoload class cùng FQN → delegate |
| **AST rename + adapter** | Compile-time | Đổi FQN + generate adapter cho signature lệch |
| **Structural adaptation** | Class có global state / static singleton | Chuyển sang scoped service trong Spiral IoC |

**Quy tắc structural**:
- Static method gọi state → chuyển thành instance method trên service, shim giữ `__callStatic`
- Property global (`$GLOBALS['wp_query']`) → scoped object reset sau mỗi request (xem §10.6)
- Hook trong class (`do_action` trong method) → Spiral Event Dispatcher, bridge đăng ký lại
- Magic `__get`/`__set` trên WP object → giữ nguyên behavior qua shim (`WP_Post::__get` → meta)

### 10.5.3 Database & Query (9)

| Source | Target | Mode | Ghi chú |
|--------|--------|------|---------|
| `wpdb` | `PrestoWpdb` | s | §10.3.5, 05 §5.1 |
| `WP_Query` | `Post\PostQuery` | sr | meta_query → JSONB ops |
| `WP_Tax_Query` | `Taxonomy\TaxQuery` | sr | |
| `WP_Meta_Query` | `Post\MetaQuery` | sr | → `meta->` operators |
| `WP_User_Query` | `User\UserQuery` | sr | |
| `WP_Comment_Query` | `Comment\CommentQuery` | sr | |
| `WP_Term_Query` | `Term\TermQuery` | sr | |
| `WP_Date_Query` | `Query\DateQuery` | sr | |
| `WP_Block_Query` | *(x)* | x | Không có block system |

### 10.5.4 Content, Posts & Blocks (9)

| Source | Target | Mode | Ghi chú |
|--------|--------|------|---------|
| `WP_Post` | `Post\PostEntity` | sr | `__get` → meta fallback |
| `WP_Post_Type` | `Post\PostType` | sr | |
| `WP_Embed` | `Post\Embed` | s | |
| `WP_Block` | `Block\BlockWrapper` | sr | Gutenberg block wrapper |
| `WP_Block_Type` | `Block\BlockType` | sr | |
| `WP_Block_Patterns_Registry` | `Block\PatternRegistry` | s | |
| `WP_HTML_Tag_Processor` | `Html\TagProcessor` | sr | Re-implement |
| `WP_HTML_Processor` | `Html\HtmlProcessor` | sr | |
| `WP_SimplePie_Sanitize_KSES` | *(x)* | x | Feed sanitize → `Kses` |

### 10.5.5 Users & Roles (7)

| Source | Target | Mode | Ghi chú |
|--------|--------|------|---------|
| `WP_User` | `User\UserEntity` | sr | |
| `WP_Roles` | `User\RoleRegistry` | sr | |
| `WP_Role` | `User\Role` | sr | |
| `WP_Session_Tokens` | `Auth\SessionTokenManager` | sr | |
| `WP_User_Meta_Session_Tokens` | `Auth\MetaSessionTokens` | sr | |
| `WP_Application_Passwords` | `Auth\ApplicationPasswords` | sr | |
| `WP_User_Query` | *(đã ở 10.5.3)* | — | |

### 10.5.6 Terms, Comments & Meta (6)

| Source | Target | Mode | Ghi chú |
|--------|--------|------|---------|
| `WP_Term` | `Term\TermEntity` | sr | |
| `WP_Taxonomy` | `Taxonomy\Taxonomy` | sr | |
| `WP_Comment` | `Comment\CommentEntity` | sr | |
| `WP_List_Util` | `Collection\ListUtil` | sr | `array_map`/`wp_list_filter` helper |
| `WP_Object_Cache` | `Cache\CacheRepository` | sr | |
| `WP_Metadata_Lazyloader` | `Meta\LazyLoader` | s | |

### 10.5.7 HTTP, Files & Upgrader (9)

| Source | Target | Mode | Ghi chú |
|--------|--------|------|---------|
| `WP_Http` | `Http\HttpClient` | sr | PSR-18 client |
| `WP_Http_Curl` | *(ẩn)* | n | Driver nội bộ |
| `WP_Http_Streams` | *(ẩn)* | n | |
| `WP_HTTP_Proxy` | `Http\Proxy` | s | |
| `WP_Filesystem_Base` | `Filesystem\Filesystem` | sr | |
| `WP_Filesystem_Direct` | `Filesystem\LocalAdapter` | sr | |
| `WP_Filesystem_FTPext` | `Filesystem\FtpAdapter` | s | |
| `WP_Filesystem_SSH2` | `Filesystem\SshAdapter` | s | |
| `WP_Upgrader` | `Plugin\Upgrader` | sr | Dùng Composer/Git mirror |

### 10.5.8 REST API (7)

| Source | Target | Mode | Ghi chú |
|--------|--------|------|---------|
| `WP_REST_Request` | `Rest\RestRequest` | sr | |
| `WP_REST_Response` | `Rest\RestResponse` | sr | |
| `WP_REST_Server` | `Rest\RestServer` | sr | |
| `WP_REST_Controller` | `Rest\BaseController` | sr | |
| `WP_REST_Posts_Controller` | `Rest\PostsController` | sr | |
| `WP_REST_Users_Controller` | `Rest\UsersController` | sr | |
| `WP_REST_Meta_Fields` | `Rest\MetaFieldsController` | sr | |

### 10.5.9 Theme, Customizer & Admin UI (13)

| Source | Target | Mode | Ghi chú |
|--------|--------|------|---------|
| `WP_Theme` | `Theme\ThemeEntity` | sr | |
| `WP_Customize_Manager` | *(x)* | x | Gutenberg/FSE thay thế Customizer |
| `WP_Customize_Panel` | *(x)* | x | |
| `WP_Customize_Section` | *(x)* | x | |
| `WP_Customize_Control` | *(x)* | x | |
| `WP_Customize_Setting` | `OptionRepository` | r | Setting → option |
| `WP_Customize_Image_Control` | *(x)* | x | |
| `WP_Widget` | `Widget\WidgetBridge` | sr | Legacy widget shim (Gutenberg) |
| `WP_Widget_Factory` | `Widget\WidgetFactory` | s | |
| `WP_List_Table` | `Dashboard\TableComponent` | sr | SolidJS table (08) |
| `WP_Admin_Bar` | *(x)* | x | |
| `WP_Screen` | `Screen\Screen` | sr | |
| `WP_Nav_Walker` | `Theme\NavWalker` | sr | |

### 10.5.10 Media, Scripts & Style (8)

| Source | Target | Mode | Ghi chú |
|--------|--------|------|---------|
| `WP_Image_Editor` | `Media\ImageEditor` | sr | |
| `WP_Image_Editor_GD` | `Media\GdEditor` | sr | |
| `WP_Image_Editor_Imagick` | `Media\ImagickEditor` | sr | |
| `WP_Scripts` | `Asset\ScriptRegistry` | sr | |
| `WP_Styles` | `Asset\StyleRegistry` | sr | |
| `WP_Dependencies` | `Asset\DependencyGraph` | sr | |
| `WP_Script_Module_Registry` | `Asset\ModuleRegistry` | sr | |
| `WP_Locale` | `I18n\Locale` | sr | |

### 10.5.11 Core Utilities & Recovery (8)

| Source | Target | Mode | Ghi chú |
|--------|--------|------|---------|
| `WP_Error` | `Error\PrestoError` | sr | Throwable-compatible |
| `WP_Textdomain_Registry` | `I18n\TextdomainRegistry` | sr | |
| `WP_Translation_Controller` | `I18n\TranslationController` | sr | |
| `WP_Recovery_Mode` | `Recovery\RecoveryMode` | sr | |
| `WP_Fatal_Error_Handler` | `Recovery\FatalHandler` | sr | Worker-safe |
| `WP_Paused_Extensions_Storage` | `Recovery\PausedStorage` | sr | |
| `WP Cron` (không class) | `Scheduler` | sr | Xem 10.4.18 |
| `WP_Navigation` | `Theme\Navigation` | sr | |

**Tổng**: 55+ class được map.

### 10.5.12 Ví Dụ Compile-time Rewrite

**Trước** (WordPress):
```php
class CF7_Send {
    public function send($to) {
        $args = array(
            'post_type' => 'cf7_form',
            'meta_key'  => '_cf7_active',
        );
        $q = new WP_Query($args);
        if ($q->have_posts()) {
            $post = get_post($q->post->ID);
            $title = esc_html(get_the_title($post));
            wp_mail($to, $title, $post->post_content);
        }
        wp_die();
    }
}
```

**Sau** (PrestoWorld, đã compile):
```php
use PrestoWorld\Core\Post\PostQuery;
use PrestoWorld\Core\Post\PostRepository;
use PrestoWorld\Core\Escape;
use PrestoWorld\Core\MailService;
use PrestoWorld\Core\Legacy\LegacyTerminationException;

class CF7_Send {
    public function send($to) {
        $q = new PostQuery([
            'post_type' => 'cf7_form',
            'meta_key'  => '_cf7_active',
        ]);
        if ($q->havePosts()) {
            $post = PostRepository::find($q->post->ID);
            $title = Escape::html(PostRepository::title($post));
            MailService::send($to, $title, $post->content);
        }
        throw new LegacyTerminationException();
    }
}
```

---

## 10.6 Layer 4: User Function Transformer

> **Design Decision** ([02 §2.8](./02-architecture.md)): Sau khi compile, **không một
> user-defined function nào được phép tồn tại** trong package plugin/theme. Tất cả
> phải là PSR-4 static method trên class có namespace.

### 10.6.1 Nguyên Tắc Hoạt Động

**Vì sao xóa user functions?**

| Vấn đề | Giải thích |
|--------|------------|
| **Boot time** | PHP phải parse & compile **toàn bộ** file chứa user functions khi `include`/`require`, dù request đó không cần hàm nào trong đó |
| **Lazy autoload** | PSR-4 class chỉ được nạp khi thực sự dùng — `spl_autoload_register` chỉ kéo file cần thiết |
| **OpCache slot** | File `functions.php` lớn chiếm cache line không cần thiết cho nhiều request |
| **Namespace isolation** | Static method sống trong namespace → tránh name collision giữa các plugin |

### 10.6.2 Quy Tắc Đặt Tên Class

Mỗi plugin được cấp một **namespace riêng** sinh từ slug:

```
Plugin slug: contact-form-7
Generated namespace: Plugins\ContactForm7
File: compiled/contact-form-7/Helpers.php
```

Theme cũng tương tự:

```
Theme slug: my-awesome-theme
Generated namespace: Themes\MyAwesomeTheme
```

Các user functions trong cùng một file PHP được **nhóm vào một class** theo file:

```
myplugin/includes/utils.php     → Plugins\MyPlugin\Utils  (class Utils)
myplugin/includes/api-helper.php → Plugins\MyPlugin\ApiHelper
myplugin/functions.php          → Plugins\MyPlugin\Functions
```

### 10.6.3 AST Rewrite: Function → Static Method

**Trước** (plugin source):
```php
// myplugin/includes/utils.php

function myplugin_format_price(float $price, string $currency = 'USD'): string {
    return number_format($price, 2) . ' ' . $currency;
}

function myplugin_get_settings(): array {
    return get_option('myplugin_settings', []);
}

function myplugin_log(string $msg): void {
    error_log('[MyPlugin] ' . $msg);
}
```

**Sau** (compiled output — Pass 4):
```php
// compiled/myplugin/Utils.php

namespace Plugins\MyPlugin;

use PrestoWorld\Core\Format;
use PrestoWorld\Core\OptionRepository;

final class Utils
{
    public static function formatPrice(float $price, string $currency = 'USD'): string
    {
        return Format::number($price, 2) . ' ' . $currency;
        // ↑ Pass 2 đã rewrite number_format_i18n → Format::number rồi
    }

    public static function getSettings(): array
    {
        return OptionRepository::get('myplugin_settings', []);
        // ↑ Pass 2 đã rewrite get_option → OptionRepository::get rồi
    }

    public static function log(string $msg): void
    {
        error_log('[MyPlugin] ' . $msg);
    }
}
```

> **Thứ tự quan trọng**: Pass 4 chạy **sau** Pass 2 (WpFunctionPass). Nghĩa là khi
> Pass 4 nâng function lên static method, các WP function call bên trong đã được
> rewrite thành PW static calls từ Pass 2 rồi.

### 10.6.4 Rewrite Call Sites

Mọi nơi gọi user function trong plugin cũng phải được rewrite thành static call:

```php
// Trước:
$price = myplugin_format_price(99.9);

// Sau (Pass 4 rewrite call site):
use Plugins\MyPlugin\Utils;
$price = Utils::formatPrice(99.9);
```

Pass 4 xây dựng **Call Site Map** (ánh xạ `fn_name → Class::method`) trong Stage 3
(Analyze), rồi dùng map này khi traverse để thay thế đồng thời cả definition lẫn call sites.

### 10.6.5 Xử Lý Các Trường Hợp Đặc Biệt

| Tình huống | Cách xử lý |
|-----------|------------|
| Function gọi `$this` (không hợp lệ) | Không thể xảy ra — user function không có `$this` |
| Function dùng `global $wpdb` | Rewrite `global $wpdb` → inject `PrestoWpdb` qua tham số hoặc static call |
| `extract()` / `compact()` trong function | Giữ nguyên, ghi cảnh báo trong report |
| `create_function()` (PHP cũ) | Rewrite thành anonymous function hoặc static closure |
| Conditional function def (`if (!function_exists(...))`) | Giữ guard → chuyển thành `if (!method_exists(...))` |
| Recursive function (gọi chính nó) | Rewrite call site bên trong thành `self::methodName()` |
| Function ẩn danh / closure (không phải user fn) | **Giữ nguyên** — chỉ transform named global functions |
| `pluggable function` (WP core define nếu chưa có) | Ghi chú trong report, shim xử lý |

### 10.6.6 Tích Hợp Hook: add_action với User Function Callback

Đây là trường hợp phổ biến nhất:

```php
// Trước (plugin source):
add_action('save_post', 'myplugin_on_save_post', 10, 2);

function myplugin_on_save_post(int $postId, WP_Post $post): void {
    myplugin_log('Post saved: ' . $postId);
}
```

```php
// Sau (compiled — kết hợp Pass 2 + Pass 4):
use Plugins\MyPlugin\Hooks;
use PrestoWorld\Core\Legacy\LegacyRegistry;

// add_action → LegacyRegistry::recordHook (Pass 2)
// callback string 'myplugin_on_save_post' → [Hooks::class, 'onSavePost'] (Pass 4)
LegacyRegistry::recordHook('action', 'save_post', [Hooks::class, 'onSavePost'], 10, 2);

// compiled/myplugin/Hooks.php
namespace Plugins\MyPlugin;
use Plugins\MyPlugin\Utils;

final class Hooks
{
    public static function onSavePost(int $postId, \PrestoWorld\Core\Post\PostEntity $post): void
    {
        Utils::log('Post saved: ' . $postId);
    }
}
```

### 10.6.7 UserFunctionPass Implementation

```php
namespace PrestoWorld\Core\Compiler\Passes;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;
use PhpParser\BuilderFactory;

/**
 * Pass 4: Rewrite tất cả named global functions thành static methods
 * trên PSR-4 class theo file → namespace mapping.
 */
class UserFunctionPass extends NodeVisitorAbstract
{
    private BuilderFactory $factory;
    /** @var array<string, string> fn_name → ClassName::methodName */
    private array $callSiteMap = [];
    private array $generatedClasses = [];

    public function __construct() {
        $this->factory = new BuilderFactory();
    }

    public function beforeTraverse(array $nodes): ?array
    {
        // Stage 3 đã populate $this->context->userFunctions
        foreach ($this->context->userFunctions as $fn) {
            $className = $this->resolveClassName($fn->file);
            $methodName = $this->toMethodName($fn->name);
            $this->callSiteMap[$fn->name] = $className . '::' . $methodName;
        }
        return null;
    }

    public function leaveNode(Node $node): Node|array|null
    {
        // Rewrite function definition → static method
        if ($node instanceof Node\Stmt\Function_) {
            return $this->rewriteToStaticMethod($node);
        }

        // Rewrite call site: myplugin_fn() → ClassName::methodName()
        if ($node instanceof Node\Expr\FuncCall
            && $node->name instanceof Node\Name
            && isset($this->callSiteMap[$node->name->toString()])
        ) {
            return $this->rewriteCallSite($node);
        }

        // Rewrite string callback: 'myplugin_fn' → [Class::class, 'methodName']
        if ($node instanceof Node\Scalar\String_
            && isset($this->callSiteMap[$node->value])
        ) {
            return $this->rewriteStringCallback($node->value);
        }

        return null;
    }

    private function rewriteToStaticMethod(Node\Stmt\Function_ $fn): Node\Stmt\ClassMethod
    {
        return (new Node\Stmt\ClassMethod($this->toMethodName($fn->name->name), [
            'flags'  => Node\Stmt\Class_::MODIFIER_PUBLIC | Node\Stmt\Class_::MODIFIER_STATIC,
            'params' => $fn->params,
            'returnType' => $fn->returnType,
            'stmts' => $fn->stmts,
        ]));
    }

    private function toMethodName(string $fnName): string
    {
        // myplugin_format_price → formatPrice (camelCase, strip plugin prefix)
        $parts = explode('_', $fnName);
        return lcfirst(implode('', array_map('ucfirst', $parts)));
    }

    private function resolveClassName(string $filePath): string
    {
        // /path/to/plugin/includes/utils.php → Utils
        return ucfirst(str_replace(['-', '_'], '', basename($filePath, '.php')));
    }
}
```

### 10.6.8 Ví Dụ Đầy Đủ: Plugin Compile

**Plugin source** (`woocommerce-lite/includes/price.php`):
```php
<?php
function wclite_format_price(float $amount): string {
    return '$' . number_format_i18n($amount, 2);
}

function wclite_get_tax_rate(): float {
    return (float) get_option('wclite_tax_rate', 0.1);
}

add_filter('woocommerce_price_format', 'wclite_format_price');
```

**Compiled output** (`compiled/woocommerce-lite/Price.php`):
```php
<?php
declare(strict_types=1);

namespace Plugins\WoocommerceLite;

use PrestoWorld\Core\Format;
use PrestoWorld\Core\OptionRepository;
use PrestoWorld\Core\Legacy\LegacyRegistry;

final class Price
{
    public static function formatPrice(float $amount): string
    {
        return '$' . Format::number($amount, 2);  // Pass 2: number_format_i18n → Format::number
    }

    public static function getTaxRate(): float
    {
        return (float) OptionRepository::get('wclite_tax_rate', 0.1); // Pass 2: get_option → OptionRepository::get
    }
}

// add_filter callback rewritten from string → [Class, method] (Pass 4 + Pass 2)
LegacyRegistry::recordHook('filter', 'woocommerce_price_format', [Price::class, 'formatPrice']);
```

---

## 10.7 Runtime Shim Layer

### 10.7.1 Nạp Shim

```php
// app/src/Bootloader/LegacyBridgeBootloader.php (03 §3.1.1)
class LegacyBridgeBootloader extends Bootloader
{
    public function boot(ShimLoader $shims): void
    {
        // Nạp theo nhóm, tối ưu cho OpCache (Mode B - 02 §2.4)
        $shims->loadGroup('escaping');   // wp-escape.php
        $shims->loadGroup('options');    // wp-options.php
        $shims->loadGroup('formatting'); // wp-format.php
        // ... 18 nhóm ở 10.4
        $shims->registerAutoload();      // Class shims (10.5)
    }
}
```

### 10.7.2 Yêu Cầu Với Long-Running Worker (RoadRunner)

> **Rủi ro**: shim giữ state global/static → **lọt sang request tiếp theo**.

**Checklist reset sau mỗi request**:

| State | Vị trí | Reset |
|-------|--------|-------|
| Hook registry đệm trong RAM | `LegacyInvoker::$currentHook` | Clear sau `do_action` |
| `$GLOBALS['wp_query']`, `$post` | Shim globals | `RequestScope::reset()` |
| `WP_Query::$posts` static cache | Class shim | Scoped container (non-singleton) |
| `AssetManager` enqueue list | `Asset\ScriptRegistry` | Flush sau khi render header |
| Object cache (`wp_cache_*`) | `CacheRepository` | Request-scoped keys hoặc flush |
| `WP_Error` accumulators | Error shim | Clear sau response |
| Locale (`determine_locale`) | `I18n\Locale` | Reset về app default |

```php
// Spiral middleware - chạy trước mỗi request
class ResetLegacyStateMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, HandlerInterface $handler): ResponseInterface
    {
        try {
            return $handler->handle($request);
        } finally {
            LegacyState::reset(); // Xóa toàn bộ global shim state
        }
    }
}
```

**Worker pool config liên quan** (`.rr.yaml`, 02 §2.4):
```yaml
pool:
  max_jobs: 512   # Force recycle trước khi leak tích lũy
  destroy_timeout: 60s
```

### 10.7.3 Shim Resolution Order

```
1. Compile-time rewrite đã xử lý?  → Gọi thẳng PW service
2. Có shim function?               → Gọi shim (delegate qua IoC)
3. Có shim class?                  → Autoload shim class
4. Không có                        → Ghi vào report `fallback_shims[]`
                                     + gọi nguyên bản trong sandbox isolation
```

---

## 10.8 Translation Maps & Distribution

### 10.8.1 Mở Rộng Map Format ([07 §7.4](./07-cloudflare.md))

```json
{
  "version": "2.1.0",
  "created_at": "2026-10-08T00:00:00Z",
  "applies_to": "1.5.0+",
  "rules": [
    {
      "kind": "sql",
      "pattern": "/AUTO_INCREMENT/i",
      "replacement": "SERIAL",
      "target": "postgresql",
      "priority": "high"
    },
    {
      "kind": "function",
      "source": "esc_html",
      "target": "PrestoWorld\\Core\\Escape::html",
      "mode": "sr",
      "since": "1.0.0"
    },
    {
      "kind": "class",
      "source": "WP_Query",
      "target": "PrestoWorld\\Core\\Post\\PostQuery",
      "mode": "sr",
      "structural": { "global_state": "scoped" }
    }
  ]
}
```

### 10.8.2 Trust & Versioning

- Map ký theo version; client verify trước khi apply
- Offline fallback: map cache tại `storage/framework/mappings/`
- Hotfix: patch function/class map không cần deploy lại binary (Cloudflare Worker, 07 §7.4.2)

---

## 10.9 Compatibility Report & Dashboard

### 10.9.1 Report Format

```json
{
  "plugin": "contact-form-7",
  "mapping_version": "2.1.0",
  "compatibility_score": 92,
  "stats": {
    "files_scanned": 42,
    "queries_transformed": 14,
    "wp_functions_mapped": 87,
    "classes_mapped": 12,
    "user_functions_compiled": 23,
    "user_functions_skipped": 1
  },
  "user_function_map": {
    "cf7_format_subject": "Plugins\\ContactForm7\\Formatter::formatSubject",
    "cf7_validate_email": "Plugins\\ContactForm7\\Validator::validateEmail"
  },
  "issues": [
    { "type": "unsupported_function", "symbol": "wp_editor", "file": "admin.php", "line": 88 },
    { "type": "unresolved_class",   "symbol": "CF7_Custom_Send", "fallback": "shim" },
    { "type": "unsafe_sql",         "file": "db.php", "line": 41 },
    { "type": "user_fn_skipped",    "symbol": "cf7_legacy_compat", "reason": "uses extract()" }
  ]
}
```

### 10.9.2 Compatibility Score

Nâng cấp `CompatibilityScorer` ([06 §6.10.2](./06-legacy-support.md)) để đọc output compiler:

| Tiêu chí | Trừ điểm |
|----------|----------|
| Hàm unsupported (`mode: x`) | -20/mỗi nhóm |
| Query không parse được | -30 |
| Class cần structural adapter | -10 |
| Toàn bộ shim (chưa compile) | -5 (đã chạy được, chưa tối ưu) |
| User function còn sót (chưa compile) | -3/hàm (boot time penalty) |
| User function bị skip (không rewrite được) | -1/hàm |

### 10.9.3 Dashboard Panel ([08 §8.4](./08-dashboard.md))

- **Compiler Report** card trên plugin detail: score, stats, issues list
- Nút **"Run Compile"** → gọi `pw compile` qua RPC (03 §3.4)
- Badge: `Compiled` (PW-native) / `Shimmed` (chạy qua shim) / `Incompatible`
- **User Functions** tab: bảng ánh xạ `fn_name → Class::method` với link đến file compiled

---

## 10.10 Validation Strategy

### 10.10.1 Golden Test Suite

```
tests/compatibility/
├── plugins/          # Top 100 wp.org plugins (frozen versions)
├── fixtures/         # DB seed + expected output HTML/JSON
└── Runner.php        # So sánh behavior: WP-ref vs PW
```

- **Behavior diff**: chạy request mẫu trên 2 nền tảng, so output
- **Query diff**: log SQL transform, so sánh kết quả set (không so string)
- **Fuzzing**: inject query/random settings, đảm bảo không crash worker

### 10.10.2 Benchmarks

| Metric | Target |
|--------|--------|
| Compile time (plugin 50 files) | < 5s |
| Shim overhead / call | < 50µs |
| Compiled plugin TTFB | Không giảm so với native PW |
| Map apply (runtime) | < 1ms (cached) |
| Pass 4 rewrite (per function) | < 1ms |

---

## 10.11 Roadmap Cross-reference

| Milestone | Nội dung | File |
|-----------|----------|------|
| **1.5** | Runtime Shim Layer: function + class shims, state reset middleware | [09 §9.2](./09-roadmap.md) |
| **2.5** | Compile-time AST Compiler: pipeline, CLI, mapping registry + Pass 4 User Fn | [09 §9.3](./09-roadmap.md) |
| **3.1** | Translation Map distribution (mở rộng format `kind`) | [09 §9.4](./09-roadmap.md), [07 §7.4](./07-cloudflare.md) |
| **4.2** | Mở rộng mapping: +100 hàm, +20 class, auto-fix rules; nâng cấp Pass 4 coverage | [09 §9.5](./09-roadmap.md) |

**Rủi ro liên quan**: "Function/class shim incomplete → plugin break"
([09 §9.7 Risk Management](./09-roadmap.md))

**Rủi ro Pass 4**: "User function dùng call-by-reference nội bộ khó rewrite" → 
fallback: giữ user function nhưng wrap trong anonymous static closure, ghi cảnh báo.
