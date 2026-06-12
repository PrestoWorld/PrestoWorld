# Module Development Guide

## Module Types

| Type      | Purpose                          | Load order | Example              |
|-----------|----------------------------------|------------|----------------------|
| `core`    | Framework-required functionality | First      | Schema, Search       |
| `support` | Optional feature modules         | Normal     | Gutenberg            |
| `theme`   | Theme / frontend presentation    | Last       | —                    |
| `plugin`  | Third-party / extension          | Last       | —                    |

## Module Structure

```
modules/<module-name>/
├── manifest.json          # Module metadata (name, version, deps)
├── Module.php             # Entry point — extends Witals\Framework\Module\Module
├── src/                   # PHP source (optional, PSR-4 autoloaded)
├── views/                 # Stempler templates (optional)
├── routes/                # Route files (optional)
├── migrations/            # Database migrations (optional)
└── helpers.php            # Global helpers (optional, loaded manually)
```

## `manifest.json` Conventions

```json
{
    "name": "my-module",
    "version": "1.0.0",
    "description": "What this module does",
    "type": "support",
    "priority": 50,
    "namespace": "PrestoWorld\\Modules\\MyModule",
    "entry": "Module.php",
    "requires": { "php": "^8.1" },
    "dependencies": {
        "schema": "^1.0.0"
    },
    "provides": ["my.feature"],
    "providers": [],
    "autoload": {
        "psr-4": {
            "PrestoWorld\\Modules\\MyModule\\": "."
        }
    }
}
```

### Field Rules

- **name**: lowercase, kebab-case, duy nhất trong toàn bộ modules
- **type**: `core` | `support` | `theme` | `plugin`
- **priority**: 0–100 (thấp → load trước). Core: 0–30, Support: 40–70, Theme/Plugin: 80–100
- **entry**: luôn là `Module.php`
- **namespace**: `PrestoWorld\Modules\<PascalCaseModuleName>\`
- **dependencies**: key = tên module, value = semver constraint (`^1.0`, `~2.0`, `>=1.5`)
- **provides**: array string identifiers để module khác có thể kiểm tra availability
- **providers**: array các service provider class names (optional)

## `Module.php` Template

```php
<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\MyModule;

use Witals\Framework\Module\Module as WitalsModule;

class Module extends WitalsModule
{
    public function register(): void
    {
        // Bind services, register providers
    }

    public function boot(): void
    {
        // Register hooks, load helpers
    }
}
```

## Communication Between Modules

Modules **không gọi trực tiếp** nhau. Dùng các cơ chế sau:

1. **Container bindings**: `$this->app->make(Service::class)`
2. **Hooks / Events**: `add_action('event.name', $callback)` / `apply_filters('filter.name', $value)`
3. **Shared contracts**: Module A định nghĩa interface, Module B implement

## Best Practices

- Mỗi module chỉ làm một việc (Single Responsibility)
- Luôn khai báo `dependencies` trong manifest.json
- helpers.php chỉ chứa function_exists-guarded global functions
- Views đặt trong `views/` thư mục con của module
- Migrations đặt trong `migrations/` thư mục con
- Không hardcode đường dẫn; dùng `$this->path` hoặc `module_path('name')`
