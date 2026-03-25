# 💎 PrestoWorld: DigitalCore
### Premium Enterprise Management & Licensing Framework

PrestoWorld **DigitalCore** is a high-performance, standardized admin dashboard and core management system built on the **Witals Framework**. It is designed to bridge legacy WordPress ecosystems with modern, scalable PHP architectures, providing a premium "Software-as-a-Service" (SaaS) experience for managing licenses, products, and customers.

---

## ✨ Architectural Pillars

### 1. Unified Stempler Rendering
DigitalCore enforces a **Strict View Engine Standard**. All web responses (Admin & Frontend) must be rendered through a registered template engine (default: **Spiral Stempler**). 
- **Enforcement**: Direct string/HTML returns from controllers are blocked by the `Http\Kernel` to maintain architecture purity.
- **Flexibility**: Supports any engine registered in the `ViewFactory` (Stempler, Native PHP, etc.).

### 2. Radical Modularization (Dashboard)
The dashboard is no longer a monolithic file. It is a **Dynamic Widget Hub**:
- **Statistics Injection**: Modules inject business metrics via the `dashboard.stats` filter.
- **Widget Registration**: Modules register interactive widgets via `dashboard.init_widgets` action using the `PrestoWorld\Context` system.
- **Plugin Integration**: Supports both native Witals modules and WordPress MU-Plugins (auto-scanned and synchronized).

### 3. WP-Bridge & Component Scanning
Seamless integration with WordPress components:
- **Scanner**: `php witals components:scan` automatically identifies Plugins, Themes, and MU-Plugins.
- **MU-Plugins Support**: Specialized handling for Must-Use plugins to ensure critical business logic is always active.

---

## 🛠️ Technology Stack

- **Core**: PHP 8.1+ (Strict Typing)
- **Framework**: [Witals Framework](https://github.com/witals/framework)
- **Engine**: Spiral Stempler (with HTML, PHP, and Dynamic grammars)
- **Database**: Layered ORM (Cycle ORM & Native MySQL support)
- **Server**: Multi-runtime (RoadRunner 3.x, Swoole, or Traditional FPM)
- **Frontend**: Vanilla CSS Design System (Glassmorphism, Dark Mode, Inter Typography)

---

## 🚀 Installation & Setup

### 1. Clone & Install
```bash
git clone https://github.com/puleeno/prestoworld.com.git
composer install
```

### 2. Environment Configuration
Copy `.env.example` to `.env` and configure your local domain:
```env
APP_NAME="PrestoWorld DigitalCore"
APP_URL=http://prestoworld.localhost
DB_DATABASE=prestoworld
DB_USERNAME=root
DB_PASSWORD=root
```

### 3. Server Initialization (RoadRunner)
```bash
# Download RoadRunner binary
composer rr:download

# Start the dev server
php witals serve --roadrunner
```

### 4. Sync Components
```bash
php witals components:scan
```

---

## 🏗️ Development Guidelines

### Creating a New Dashboard Widget
Widgets are injected using Hooks. Example in a module's `boot()` method:

```php
add_action('dashboard.init_widgets', function($context) {
    if ($context->getName() === 'admin.dashboard') {
        $context->register('my-plugin-widget', [
            'label' => 'Sales Chart',
            'render' => function() {
                return view('my-plugin::widget')->render();
            }
        ]);
    }
});
```

### Standard Web Responses
Always use the `ViewFactory` to render pages:

```php
public function index() {
    return $this->view->make('my-module/index', [
        'data' => $myData
    ])->render();
}
```

---

## 🤝 Contribution & Support

PrestoWorld is an ambitious project aimed at redefining the CMS landscape. If you find value in what we are building, please consider supporting the development:

- **Sponsor on GitHub**: [github.com/sponsors/puleeno](https://github.com/sponsors/puleeno)
- **Buy Me a Coffee**: [buymeacoffee.com/puleeno](https://buymeacoffee.com/puleeno)

Created with ❤️ by **Puleeno Nguyen** and the PrestoWorld Team.
