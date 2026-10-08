# PrestoWorld (PW) - Technical Specifications

> **The High-Performance Evolution of the WordPress Ecosystem**
> A clean-room implementation of a WordPress-compatible CMS, engineered with Spiral Framework, RoadRunner, and SolidJS.

---

## 📚 Table of Contents

- [Overview](./01-overview.md) - Bối cảnh, vấn đề và cơ hội
- [Architecture](./02-architecture.md) - Kiến trúc tổng thể
- [Backend](./03-backend.md) - Spiral Framework & RoadRunner
- [Frontend](./04-frontend.md) - SolidJS, Elementor 2, Hybrid Rendering
- [Database](./05-database.md) - PostgreSQL & SQLite Strategy
- [Legacy Support](./06-legacy-support.md) - WordPress.org Compatibility
- [Cloudflare](./07-cloudflare.md) - R2 & Workers Infrastructure
- [Dashboard](./08-dashboard.md) - SPA Dashboard UI
- [Roadmap](./09-roadmap.md) - Phases & Milestones
- [WordPress Compiler](./10-wordpress-compiler.md) - Query/Function/Class Transformers, WP → PW
- [API Specs](./11-api-specs.md) - API Documentation

---

## 🎯 Vision

PrestoWorld (PW) is a complete rebuild of the CMS concept. We preserve the user experience that made WordPress famous but replace its 20-year-old legacy engine with modern, industrial-grade technology.

**The "PW" Flip Philosophy:**
- **WordPress (WP)**: Heavy, legacy-bound, focused on "Press" (printing press/pressure)
- **PrestoWorld (PW)**: Instant speed (Presto), focused on "World" (expansive/ecosystem)

---

## 🚀 Key Features

### Performance
- **Sub-30ms response times** via RoadRunner + Spiral
- **Zero-boot latency** - PHP workers stay "hot" in RAM
- **Core Web Vitals 100** - SSR + Hybrid CSR rendering
- **Inode optimization** - SQLite vs thousands of node_modules files

### Developer Experience
- **PHP-first** - No JS framework required for widgets
- **Type-safe** - PHP 8.3 + TypeScript TSX
- **IoC Container** - Automatic dependency injection
- **Code-First Controls** - Define widgets via PHP classes

### Compatibility
- **WordPress.org Bridge** - Install 60,000+ plugins seamlessly
- **Legacy Hook System** - Full support for add_action/add_filter
- **Shadow wp-admin** - Classic dashboard compatibility
- **MySQL → PostgreSQL/SQLite** - Intelligent query translation
- **WP → PW Compiler** - 3 transformers (Query/Function/Class), runtime shims + AST rewrite

### Infrastructure
- **Shared Hosting Ready** - SQLite + OpCache mode
- **VPS Optimized** - RoadRunner worker pool
- **SaaS Native** - Auto-scaling, stateless architecture
- **Edge Distribution** - Cloudflare R2 + Workers

---

## 🛠 Tech Stack

### Core Runtime
- **Framework**: Spiral Framework 3.x (PHP 8.3+)
- **Server**: RoadRunner (Golang Application Server)
- **Database (Main)**: PostgreSQL 16+
- **Database (Legacy)**: SQLite 3 (for wp.org plugins)

### Frontend
- **UI Framework**: SolidJS + TSX
- **Page Builder**: Elementor 2 (Refactored)
- **Rendering**: Hybrid SSR/CSR with PHP Templates
- **State Management**: IndexedDB + SolidJS Signals

### Infrastructure
- **Storage**: Cloudflare R2
- **API**: Cloudflare Workers
- **Distribution**: Cloudflare CDN

---

## 📄 License

**MIT License**

PrestoWorld is a Clean-room Implementation. Because the core logic is built from scratch on the Spiral Framework, we are able to offer the entire platform under the MIT License.

> **Disclaimer**: PrestoWorld is an independent community project. It is compatible with the WordPress ecosystem but contains no code from WordPress.org or Automattic.

---

## 🤝 Contributing

This document set is the complete technical specification for PrestoWorld. All implementations should follow these specifications precisely.

For questions or clarifications, please refer to the individual specification documents in this directory.
