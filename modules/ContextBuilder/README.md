# PrestoWorld Context Builder

Context Builder là hệ thống xây dựng layout cho các loại data (context type) trong PrestoWorld. Nó sử dụng Gutenberg editor làm công cụ soạn thảo và hỗ trợ hybrid rendering (SSR/CSR) cho từng block.

## Kiến trúc

```
CONTEXT TYPE (taxonomy, post, page, custom)
    │
    ├── CONTEXT BUILDER (admin UI)
    │   └── Gutenberg Editor (soạn thảo content/layout)
    │
    └── CONTEXT LOADER (render.php)
        └── Render layout ra frontend (hybrid SSR/CSR)
```

### Template Hierarchy

```
template-canvas.php (parent canvas — HTML shell, regions)
    └── render.php (child — render layout cho context type)
```

### File Storage

Chỉ chấp nhận 2 loại file:

1. **Default template** → nằm trong THEME:
   - `/themes/{theme}/templates/{context-type}/template-canvas.php`
   - `/themes/{theme}/templates/{context-type}/render.php`
   - `/themes/{theme}/templates/{context-type}/layout.html`

2. **File đã chỉnh sửa** → nằm trong STORAGE:
   - `/storage/contexts/{context-type}/layout.html`
   - `/storage/contexts/{context-type}/template-canvas.php`

**Resolve order:** STORAGE (user-edited) → THEME (default) → fallback.

## Hybrid Rendering

Context Loader render **partial hybrid** — mỗi block tự quyết định CSR hay SSR dựa trên settings của nó.

### Rules (§4.3.3)

- **Parent SSR → child có thể SSR hoặc CSR**
- **Parent CSR → child bắt buộc CSR**

### Block Render Mode

Mỗi block có property `renderMode`:
- `'ssr'` — PHP render server-side
- `'csr'` — placeholder + Bridge.js fetch từ `/pw-api/v1/render-block`

## Template Engine

`ContextLoader` là template engine chính (single engine). Frontend render
đi qua `App\Services\ContentRenderer`, engine này chọn:

1. `ClassicThemeEngine` — nếu theme active là theme WordPress cấu trúc cũ
   (pre-Gutenberg, có `index.php`/`style.css`, không có `theme.json`).
2. `ContextLoader` — nếu là block theme (có `theme.json` + `templates/`).

```php
$html = $contextLoader->renderTemplate('index.html', $data);
```

## Gutenberg Integration

Gutenberg fork được cấu hình để gọi PrestoWorld API:

- **REST API:** `/wp/v2/` → `/pw-api/v1/`
- **Authentication:** WP nonces → PrestoWorld nonces
- **Build:** `webpack.presto.js` với `PRESTO_API=/pw-api/v1`

### Integration Points

1. **Full Site Edit** — Context Builder là full site, edit toàn bộ layout.html
2. **Content Edit** — Page edit, edit content bên trong layout

## REST API

### Context Builder API

- `GET /pw-api/v1/context-builder/context-types` — danh sách context types
- `GET /pw-api/v1/context-builder/blocks` — danh sách blocks
- `GET /pw-api/v1/context-builder/layout/{context_type}` — lấy layout
- `POST /pw-api/v1/context-builder/layout/{context_type}` — lưu layout
- `GET /pw-api/v1/context-builder/content/{context_type}/{id}` — lấy content
- `POST /pw-api/v1/context-builder/content/{context_type}/{id}` — lưu content
- `POST /pw-api/v1/context-builder/render/{context_type}` — render context

### Gutenberg API

- `GET /pw-api/v1/settings` — editor settings
- `GET /pw-api/v1/media` — danh sách media
- `POST /pw-api/v1/media/upload` — upload media
- `GET /pw-api/v1/templates` — danh sách templates
- `GET /pw-api/v1/templates/{slug}` — lấy template content
- `PUT /pw-api/v1/templates/{slug}` — lưu template
- `GET /pw-api/v1/template-parts` — danh sách template parts
- `GET /pw-api/v1/block-patterns` — danh sách block patterns
- `GET /pw-api/v1/block-types` — danh sách block types
- `GET /pw-api/v1/contexts` — danh sách context types
- `GET /pw-api/v1/contexts/{type}/layout` — lấy context layout
- `PUT /pw-api/v1/contexts/{type}/layout` — lưu context layout

## Cấu trúc Module

```
modules/ContextBuilder/
├── Module.php                    # Module bootstrap
├── module.json                   # Module metadata
├── ContextType.php               # Định nghĩa context type
├── ContextRegistry.php           # Quản lý context types
├── ContextLoader.php             # Hybrid renderer
├── ContextBuilder.php            # Service cho Gutenberg integration
├── BlockRegistry.php             # Quản lý blocks
├── BaseBlock.php                 # Base class cho blocks
├── UnsupportedBlockException.php # Exception cho block chưa hỗ trợ
├── Parser/
│   └── BlockParser.php           # Parse HTML templates với block comments
├── Block/
│   ├── HeadingBlock.php          # core/heading
│   ├── ParagraphBlock.php        # core/paragraph
│   ├── ImageBlock.php            # core/image
│   ├── QueryLoopBlock.php        # core/query-loop
│   ├── DynamicDataLayoutBlock.php    # jankx/dynamic-data-layout
│   ├── DynamicDataTemplateBlock.php  # jankx/dynamic-data-template
│   └── HumanReadablePostDateBlock.php # jankx/human-readable-post-date
├── Gutenberg/
│   └── GutenbergIntegration.php  # Integrate Gutenberg vào PrestoWorld
├── Rest/
│   ├── ContextBuilderController.php # Context Builder REST API
│   └── GutenbergRestController.php  # Gutenberg REST API
└── tests/
    └── ContextLoaderTest.php     # Unit tests
```

## Sử dụng

### Render context type

```php
$contextLoader = $app->make(ContextLoader::class);
$contextType = $app->make(ContextRegistry::class)->get('post');

$html = $contextLoader->render($contextType, [
    'post' => $post,
    'terms' => $terms,
]);
```

### Render template file

```php
$html = $contextLoader->renderTemplate('index.html', $data);
```

### Query posts

```php
$posts = $app->make(\PrestoWorld\Modules\Schema\PostRepository::class)->findAll([
    'post_type' => 'post',
    'posts_per_page' => 10,
]);
```

### Lưu layout

```php
$contextBuilder = $app->make(ContextBuilder::class);
$contextBuilder->saveLayout('post', [
    'header' => [
        ['type' => 'presto/heading', 'attributes' => ['content' => 'Header']],
    ],
    'content' => [
        ['type' => 'presto/paragraph', 'attributes' => ['content' => 'Content']],
    ],
]);
```

## Testing

```bash
php modules/ContextBuilder/tests/ContextLoaderTest.php
```

## Gutenberg Build

```bash
cd /Users/puleeno/Projects/gutenberg

# Development build
npm run build:presto

# Watch mode
npm run dev:presto

# Production build
npm run build:presto:prod
```

Output: `/prestoworld.org/public/assets/gutenberg/`

## Unsupported Blocks

Nếu ContextLoader gặp block chưa đăng ký, nó sẽ throw `UnsupportedBlockException`:

```php
try {
    $html = $contextLoader->render($contextType, $data);
} catch (UnsupportedBlockException $e) {
    echo $e->getResolutionMessage();
}
```

Để thêm block mới:

1. Tạo class trong `modules/ContextBuilder/Block/`
2. Extend `PrestoWorld\Modules\ContextBuilder\BaseBlock`
3. Đăng ký trong `modules/ContextBuilder/Module.php`

## License

MIT