# PrestoWorld DigitalCore

High-performance enterprise management framework built on Witals.

## Quick Start

```bash
composer install
cp .env.example .env
php witals serve
```

See [docs/quick-start.md](docs/quick-start.md) for detailed setup.

## Documentation

| Document | Purpose |
|----------|---------|
| [ARCHITECTURE.md](ARCHITECTURE.md) | Namespace ownership, directory structure, dependency flow |
| [docs/quick-start.md](docs/quick-start.md) | Local dev setup, common commands |
| [docs/module-guide.md](docs/module-guide.md) | Module conventions, manifest.json spec |
| [framework/presto/README.md](framework/presto/README.md) | Business logic structure |

## Project Map

```
app/                  → App code (App\)
framework/presto/     → Business modules (PrestoWorld\)
framework/witals/     → Framework core (Witals\Framework\)
config/               → Runtime configuration
storage/              → Cache, logs, uploads (gitignored)
docs/                 → Developer guides
```

## Tech Stack

- PHP 8.1+, strict types
- Witals Framework (container, module system, HTTP)
- Cycle ORM / Database
- Spiral Stempler (views)
- RoadRunner / Swoole / FPM (runtimes)

