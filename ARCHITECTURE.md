# Architecture

## Namespace Ownership

```
App\                    → app/                  — Project-specific code (controllers, console commands)
PrestoWorld\            → framework/presto/     — Business logic modules (Gutenberg, Schema, Search)
Witals\Framework\       → framework/witals/src/ — Framework core (container, HTTP, module system)
Prestoworld\SearchEngine\ → vendor/              — Search engine package (composer dependency)
```

### Dependency Flow

```
┌─────────────────────────────────────────────────┐
│  App\                                            │  (app/)
│  Console, Http, Foundation                       │  App code, routes, config
└──────────────┬──────────────────────────────────┘
               │ depends on
               ▼
┌─────────────────────────────────────────────────┐
│  PrestoWorld\                                    │  (framework/presto/)
│  Modules: Gutenberg, Schema, Search              │  Business logic, feature modules
└──────────────┬──────────────────────────────────┘
               │ depends on
               ▼
┌─────────────────────────────────────────────────┐
│  Witals\Framework\                               │  (framework/witals/src/)
│  Container, HTTP, Module, Validator, Console     │  Framework — reusable, no business logic
└──────────────┬──────────────────────────────────┘
               │ depends on
               ▼
┌─────────────────────────────────────────────────┐
│  Vendor packages                                  │  (vendor/)
│  cycle/database, spiral/stempler, symfony/...   │  Third-party libraries
└─────────────────────────────────────────────────┘
```

## Directory Structure

```
prestoworld.org/
├── app/                         # Project application code (App\)
│   ├── Console/                 #   Console commands
│   ├── Foundation/              #   App bootstrap, service providers
│   └── Http/                    #   Controllers, middleware
│
├── framework/
│   ├── presto/                  # Business logic modules (PrestoWorld\)
│   │   ├── Foundation/          #   Config, providers
│   │   └── modules/             #   Feature modules
│   │       ├── Gutenberg/       #     Block renderer, theme JSON
│   │       ├── Schema/          #     Post types, repositories
│   │       └── Search/          #     PW_Query, search integration
│   │
│   └── witals/                  # Framework core (Witals\Framework\)
│       ├── src/                 #   PHP source
│       ├── app/                 #   App support (helpers, base ServiceProvider)
│       └── storage/             #   Framework cache (themejson, etc.)
│
├── config/                      # Runtime configuration (loaded by app)
├── storage/                     # Generated / runtime files
│   ├── framework/
│   │   └── cache/               #   Cache files (auto-generated)
│   ├── logs/                    #   Log files
│   └── uploads/                 #   User uploads
│
├── public/                      # Web server document root
├── tests/                       # PHPUnit tests
├── docs/                        # Developer documentation
└── vendor/                      # Composer dependencies
```

## When to put code where

| You are writing...             | Put it in...                        | Namespace               |
|-------------------------------|--------------------------------------|-------------------------|
| A controller or route         | `app/Http/`                          | `App\Http\`             |
| A console command             | `app/Console/`                       | `App\Console\`          |
| A new business feature        | `framework/presto/modules/<name>/`   | `PrestoWorld\Modules\<name>\` |
| A generic framework utility   | `framework/witals/src/`              | `Witals\Framework\`     |
| A reusable package            | Separate repo, require via composer  | As defined by package   |
