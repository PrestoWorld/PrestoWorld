# Module Manifest (`manifest.json`) Specification

Each module under `modules/` and `framework/presto/` must contain a `manifest.json` file at its root. This file drives module discovery, autoloading, and (newly) dynamic admin asset resolution.

## Required fields

| Field | Type | Description |
|-------|------|-------------|
| `name` | string | Module identifier (kebab-case or camelCase). Used as the module folder name fallback. |
| `version` | string | Semver version, e.g. `1.0.0`. |
| `namespace` | string | PSR-4 namespace root, e.g. `PrestoWorld\\Modules\\Suppliers`. |
| `entry` | string | Entry PHP file, usually `Module.php`. |
| `enabled` | bool | Whether the module is loaded at bootstrap. |
| `autoload.psr-4` | object | PSR-4 mapping relative to module root, e.g. `{ "PrestoWorld\\Modules\\Suppliers\\": "." }`. |

## Optional fields

| Field | Type | Description |
|-------|------|-------------|
| `description` | string | Human-readable description. |
| `type` | string | Module type: `support` (default, booted at startup) or `route`. |
| `priority` | int | Load order priority (lower = earlier). Default `50`. |
| `requires` | object | PHP/version requirements, e.g. `{ "php": "^8.1" }`. |
| `dependencies` | object | Other modules this depends on. |
| `provides` | array | Capability tags this module provides. |
| `providers` | array | Service provider classes to register. |
| `config` | array | Config files to load. |
| `routes` | array | Route definitions (method/path/handler). |
| `route_prefix` | string | URL prefix for module routes. |
| `migrations` | array | Migration paths. |
| `screens` | object | **New.** Maps admin screen IDs to their Vite entry file stem. Used by `SpaController::getModuleComponentUrl()` to resolve the hashed JS bundle dynamically. |

## `screens` field

The `screens` object maps one or more admin screen IDs (kebab-case) to the Vite entry file stem (without `.js`). All screens belonging to the same module typically share one entry.

Example:

```json
{
  "name": "socialfeed",
  "screens": {
    "social-feed": "socialfeed-screens",
    "social-ads": "socialfeed-screens",
    "social-gallery": "socialfeed-screens"
  }
}
```

- **Key**: screen ID (kebab-case), must match the screen ID registered in the admin menu.
- **Value**: entry file stem as produced by Vite (e.g. `socialfeed-screens`). The final URL is resolved against `public/assets/modules/{ModuleDir}/.vite/manifest.json` to find the hashed filename.

If `screens` is omitted, the system falls back to a single screen derived from the module name (kebab-cased), using `entry` as the entry stem.

## Discovery roots

Modules are discovered from two roots:

1. `modules/` — domain/business modules (BookCatalog, Ecommerce, …)
2. `framework/presto/` — core framework modules (AccessControl, Admin, SyncCore, …)

Any directory containing a `manifest.json` is treated as a module. The directory name becomes the asset folder name under `public/assets/modules/`.

## Full example

```json
{
  "name": "suppliers",
  "version": "1.0.0",
  "description": "Supplier management module",
  "type": "support",
  "priority": 50,
  "enabled": true,
  "namespace": "PrestoWorld\\Modules\\Suppliers",
  "entry": "Module.php",
  "requires": { "php": "^8.1" },
  "dependencies": {},
  "providers": [],
  "routes": [],
  "route_prefix": "",
  "provides": ["suppliers.storage"],
  "autoload": {
    "psr-4": { "PrestoWorld\\Modules\\Suppliers\\": "." }
  },
  "screens": {
    "suppliers": "suppliers-screens",
    "depots": "suppliers-screens",
    "departments": "suppliers-screens",
    "users": "suppliers-screens"
  }
}
```
