# PrestoWorld Context Builder — Implementation Summary

## Hoàn thành

### 1. ContextLoader (render theme timeless hoàn chỉnh)

**File:** `modules/ContextBuilder/ContextLoader.php`

- Render layout từ flat file `.html` (STORAGE → THEME)
- Hybrid render: SSR/CSR tuỳ thuộc vào block settings
- Throw `UnsupportedBlockException` nếu có block chưa hỗ trợ compile
- Parse HTML templates với block comments (`<!-- wp:block-name {...} -->`)
- Resolve template path: STORAGE (user-edited) → THEME (default)

**BlockParser:** `modules/ContextBuilder/Parser/BlockParser.php`
- Parse HTML templates với Gutenberg block comments
- Hỗ trợ nested blocks (recursive parsing)
- Tương thích với format của timeless theme

### 2. Template Engine

`ContextLoader` (`modules/ContextBuilder/ContextLoader.php`) là template engine
chính. Frontend render đi qua `App\Services\ContentRenderer`, chọn engine theo
loại theme:

- Block theme (`theme.json` + `templates/`, không `index.php`) → `ContextLoader`.
- Classic theme (pre-Gutenberg, có `index.php`) → `ClassicThemeEngine`.

### 3. Context Builder (Gutenberg integration)

**File:** `modules/ContextBuilder/ContextBuilder.php`

Hai integration points:
- **Full Site Edit** — edit toàn bộ layout.html
- **Content Edit** — edit content bên trong layout

### 4. Gutenberg Source (vendored)

**File:** `vendor/prestoworld/gutenberg-core/gutenberg/`

Clone đầy đủ source của WordPress/gutenberg (commit `e59587e516c`,
GPL-2.0-or-later — xem `VENDORED.md`) để build tự-contained, không phụ
thuộc checkout ngoài:

- `webpack.presto.js` — build config cho PrestoWorld (context/output trỏ về repo này)
- `packages/api-fetch/src/presto-adapter.js` — API adapter (redirect `/wp/v2/` → `/pw-api/v1/`)
- `packages/edit-post/src/index` — `editor` entry; `packages/edit-site/src/presto-boot` — `presto` entry
- `presto-styles.js` — style entry
- `package.json` (gutenberg-core) — deps exact-pinned + scripts `build:gutenberg`, `dev:gutenberg`

### 5. REST API

**Files:**
- `modules/ContextBuilder/Rest/ContextBuilderController.php`
- `modules/ContextBuilder/Rest/GutenbergRestController.php`

Endpoints:
- `/pw-api/v1/context-builder/*` — context builder operations
- `/pw-api/v1/settings`, `/pw-api/v1/media`, `/pw-api/v1/templates`, etc.

### 6. Blocks

**Files:** `modules/ContextBuilder/Block/`

Core blocks:
- `HeadingBlock` (core/heading)
- `ParagraphBlock` (core/paragraph)
- `ImageBlock` (core/image)
- `QueryLoopBlock` (core/query-loop)

Jankx custom blocks:
- `DynamicDataLayoutBlock` (timeless/dynamic-data-layout)
- `DynamicDataTemplateBlock` (timeless/dynamic-data-template)
- `HumanReadablePostDateBlock` (timeless/human-readable-post-date)

## Kiến trúc

```
CONTEXT TYPE (taxonomy, post, page, custom)
    │
    ├── CONTEXT BUILDER (admin UI + Gutenberg)
    │   ├── Full Site Edit → edit layout.html
    │   └── Content Edit → edit content
    │
    └── CONTEXT LOADER (render.php)
        ├── Parse HTML template (BlockParser)
        ├── Render blocks (BlockRenderer)
        └── Hybrid SSR/CSR per block
```

## File Storage (2 loại)

1. **Default template** → THEME: `/themes/{theme}/templates/{context-type}/`
2. **File đã chỉnh sửa** → STORAGE: `/storage/contexts/{context-type}/`

## Testing

```bash
php modules/ContextBuilder/tests/ContextLoaderTest.php
```

Kết quả: ✓ All tests passed

## Gutenberg Build

```bash
cd vendor/prestoworld/gutenberg-core
npm install                 # lần đầu
npm run build:gutenberg     # production build
npm run dev:gutenberg       # watch mode
```

Output: `public/assets/gutenberg/` (script tự `cd gutenberg/` trước khi chạy webpack)

## Unsupported Blocks

ContextLoader throw `UnsupportedBlockException` khi gặp block chưa đăng ký:

```php
try {
    $html = $contextLoader->render($contextType, $data);
} catch (UnsupportedBlockException $e) {
    echo $e->getResolutionMessage();
}
```

Để thêm block mới:
1. Tạo class trong `modules/ContextBuilder/Block/`
2. Extend `BaseBlock`
3. Đăng ký trong `Module.php`

## Files Created

### PrestoWorld (modules/ContextBuilder/)
- Module.php
- module.json
- ContextType.php
- ContextRegistry.php
- ContextLoader.php
- ContextBuilder.php
- BlockRegistry.php
- BaseBlock.php
- UnsupportedBlockException.php
- Parser/BlockParser.php
- Block/HeadingBlock.php
- Block/ParagraphBlock.php
- Block/ImageBlock.php
- Block/QueryLoopBlock.php
- Block/DynamicDataLayoutBlock.php
- Block/DynamicDataTemplateBlock.php
- Block/HumanReadablePostDateBlock.php
- Gutenberg/GutenbergIntegration.php
- Rest/ContextBuilderController.php
- Rest/GutenbergRestController.php
- tests/ContextLoaderTest.php
- README.md

### Gutenberg Source (vendor/prestoworld/gutenberg-core/gutenberg/)
- VENDORED.md (nguồn, commit, cách build/customize)
- webpack.presto.js
- presto-styles.js
- babel.config.js + tools/build-scripts/babel.config.cjs
- packages/api-fetch/src/presto-adapter.js
- packages/* (đầy đủ 127 packages)