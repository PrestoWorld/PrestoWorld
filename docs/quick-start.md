# Quick Start

## Prerequisites

- PHP 8.1+
- Composer
- SQLite (dev) or MySQL/PostgreSQL (prod)

## Setup

```bash
git clone <repo-url> prestoworld
cd prestoworld
composer install
cp .env.example .env
# Edit .env with your database credentials

# Run RoadRunner dev server
php witals serve --roadrunner

# Or use PHP built-in server
php witals serve
```

## Project Layout

```
app/              → Your project code (controllers, commands)
framework/
  presto/         → Business logic modules
  witals/         → Framework core
config/           → Runtime configuration
storage/          → Logs, cache, uploads (gitignored)
public/           → Document root
tests/            → PHPUnit tests
docs/             → Developer documentation
```

## Common Commands

| Command                          | Purpose                        |
|----------------------------------|--------------------------------|
| `php witals serve`               | Start dev server               |
| `php witals cache:clear`         | Clear framework caches         |
| `php witals make:module <name>`  | Scaffold a new module          |
| `php witals components:scan`     | Scan WP components             |
| `./vendor/bin/phpunit`           | Run tests                      |

## Module Dependencies

```
Schema ──► Search ──► PW_Query
  │
  └────► Gutenberg (block renderer uses PostRepository)
```

- **Schema**: Core post types, repositories — required by Search and Gutenberg
- **Search**: Search engine + PW_Query — depends on Schema
- **Gutenberg**: Block renderer, theme.json — depends on Schema

## Next Steps

- Read `docs/module-guide.md` for module development
- Read `ARCHITECTURE.md` for namespace and dependency overview
- Read `framework/presto/README.md` for business logic structure
