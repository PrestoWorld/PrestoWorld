# PrestoWorld Context Builder — Implementation Summary

## Hoàn thành

### 1. ContextLoader (render theme jankx hoàn chỉnh)

**File:** `modules/ContextBuilder/ContextLoader.php`

- Render layout từ flat file `.html` (STORAGE → THEME)
- Hybrid render: SSR/CSR tuỳ thuộc vào block settings
- Throw `UnsupportedBlockException` nếu có block chưa hỗ trợ compile
- Parse HTML templates với block comments (`<!-- wp:block-name {...} -->`)
- Resolve template path: STORAGE (user-edited) → THEME (default)

**BlockParser:** `modules/ContextBuilder/Parser/BlockParser.php`
- Parse HTML templates với Gutenberg block comments
- Hỗ trợ nested blocks (recursive parsing)
- Tương thích với format của jankx theme

### 2. Engine Switcher (Design Pattern)

**File:** `modules/ContextBuilder/Engine/`

Design Pattern: **Strategy + Factory**

- `ThemeEngine` (interface) — contract cho theme engine
- `PrestoWorldEngine` — implementation cho PrestoWorld
- `WordPressEngine` — implementation cho WordPress
- `EngineSwitcher` — tự động detect và switch engine

Cho phép jankx theme chạy trên cả WordPress và PrestoWorld mà không cần sửa code.

### 3. Context Builder (Gutenberg integration)

**File:** `modules/ContextBuilder/ContextBuilder.php`

Hai integration points:
- **Full Site Edit** — edit toàn bộ layout.html
- **Content Edit** — edit content bên trong layout

### 4. Gutenberg Fork

**File:** `/Users/puleeno/Projects/gutenberg/`

- `webpack.presto.js` — build config cho PrestoWorld
- `packages/api-fetch/src/presto-adapter.js` — API adapter (redirect `/wp/v2/` → `/pw-api/v1/`)
- `packages/edit-post/src/presto-editor.js` — editor entry point
- `package.json` — thêm scripts `build:presto`, `dev:presto`

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
- `DynamicDataLayoutBlock` (jankx/dynamic-data-layout)
- `DynamicDataTemplateBlock` (jankx/dynamic-data-template)
- `HumanReadablePostDateBlock` (jankx/human-readable-post-date)

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
cd /Users/puleeno/Projects/gutenberg
npm run build:presto        # Development build
npm run dev:presto          # Watch mode
npm run build:presto:prod   # Production build
```

Output: `/prestoworld.org/public/assets/gutenberg/`

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
- Engine/ThemeEngine.php
- Engine/PrestoWorldEngine.php
- Engine/WordPressEngine.php
- Engine/EngineSwitcher.php
- Gutenberg/GutenbergIntegration.php
- Rest/ContextBuilderController.php
- Rest/GutenbergRestController.php
- tests/ContextLoaderTest.php
- README.md

### Gutenberg Fork (/Users/puleeno/Projects/gutenberg/)
- webpack.presto.js
- packages/api-fetch/src/presto-adapter.js
- packages/edit-post/src/presto-editor.js
- package.json (modified)