# 04. Frontend - SolidJS, Gutenberg (Fork) & Hybrid Rendering

## 4.1 SolidJS Architecture

### 4.1.1 Why SolidJS?

**Advantages**:
- No Virtual DOM - compiles to Vanilla JS
- Ultra-small bundle size
- Signals for fine-grained reactivity
- TypeScript/TSX native support
- Perfect match with IndexedDB

**Comparison**:
| Framework | Bundle Size | Virtual DOM | Reactivity | Learning Curve |
|-----------|-------------|-------------|------------|----------------|
| React     | ~100KB      | Yes          | Component-based | High |
| Vue       | ~50KB       | Yes          | Reactive    | Medium |
| Svelte    | ~3KB        | No           | Assignment  | Low |
| SolidJS   | ~7KB        | No           | Signals     | Medium |

### 4.1.2 Project Structure

```
/public/assets/
├── /src
│   ├── /components
│   │   ├── Dashboard.tsx
│   │   ├── PluginManager.tsx
│   │   └── DatabaseMonitor.tsx
│   ├── /stores
│   │   ├── pluginStore.ts
│   │   ├── hookStore.ts
│   │   └── settingsStore.ts
│   ├── /utils
│   │   ├── indexedDB.ts
│   │   └── api.ts
│   └── app.tsx
├── /dist
│   ├── dashboard.js (compiled)
│   └── dashboard.css
```

### 4.1.3 Basic Component (TSX)

```tsx
import { createSignal } from 'solid-js';

interface PluginCardProps {
  name: string;
  version: string;
  isActive: boolean;
}

export function PluginCard(props: PluginCardProps) {
  const [isActive, setIsActive] = createSignal(props.isActive);

  const toggle = () => {
    setIsActive(!isActive());
    // Send API request
    api.togglePlugin(props.name, isActive());
  };

  return (
    <div class="plugin-card">
      <h3>{props.name}</h3>
      <span class="version">{props.version}</span>
      <button onClick={toggle}>
        {isActive() ? 'Deactivate' : 'Activate'}
      </button>
    </div>
  );
}
```

---

## 4.2 IndexedDB Integration

### 4.2.1 Schema Definition

```typescript
// utils/indexedDB.ts
interface DBSchema {
  plugins: {
    key: string;
    value: {
      id: string;
      name: string;
      version: string;
      isActive: boolean;
      hookCount: number;
    };
  };
  hooks: {
    key: string;
    value: {
      tag: string;
      priority: number;
      callback: string;
    };
  };
  fragments: {
    key: string;
    value: {
      url: string;
      html: string;
      timestamp: number;
    };
  };
}

export const db = await openDB<PrestoDB>('prestoworld', 1, {
  upgrade(db) {
    const pluginStore = db.createObjectStore('plugins', 'keyPath', 'id');
    const hookStore = db.createObjectStore('hooks', 'keyPath', 'tag');
    const fragmentStore = db.createObjectStore('fragments', 'keyPath', 'url');
  },
});
```

### 4.2.2 Fragment Caching

```typescript
export async function cacheFragment(url: string, html: string): Promise<void> {
  await db.put('fragments', {
    url,
    html,
    timestamp: Date.now(),
  });
}

export async function getFragment(url: string): Promise<string | null> {
  const cached = await db.get('fragments', url);
  
  // Return if cache is fresh (<5 minutes)
  if (cached && (Date.now() - cached.timestamp < 300000)) {
    return cached.html;
  }
  
  return null;
}
```

---

## 4.3 Hybrid Rendering System

### 4.3.1 SSR (Server-Side Rendering)

**Backend Controller**:
```php
// Witals Controller
public function index(RequestInterface $request): string
{
    $data = ['posts' => $this->posts->findAll()];

    // Check if SPA request
    if ($request->getHeaderLine('X-Requested-With') === 'PW-SPA') {
        return $this->view->render('pages/fragment', $data);
    }

    // Full page render
    return $this->view->render('pages/full-layout', $data);
}
```

**Template (PHP)**:
```php
// pages/full-layout.phtml
<!DOCTYPE html>
<html>
<head>
    <title>PrestoWorld</title>
</head>
<body>
    <div id="pw-app">
        <?= $this->load('components/header') ?>
        <main>
            <?= $this->load('pages/fragment', $data) ?>
        </main>
        <?= $this->load('components/footer') ?>
    </div>
    <script src="/assets/js/bridge.js"></script>
</body>
</html>
```

### 4.3.2 CSR (Client-Side Rendering via PHP)

**Backend Widget**:
```php
class TestWidget extends BaseWidget
{
    protected string $render_mode = 'csr'; // Client-side rendering

    public function render(array $settings, bool $is_csr_request = false): string
    {
        $data = $this->render_logic($settings);
        
        // If initial request and CSR mode → return placeholder
        if (!$is_csr_request && $this->render_mode === 'csr') {
            return sprintf(
                '<div class="pw-lazy" data-id="%s" data-type="%s" data-settings=\'%s\'></div>',
                $this->get_id(),
                static::class,
                json_encode($settings)
            );
        }

        // Render template PHP
        return $this->view->render($this->get_template(), $data);
    }
}
```

**Frontend Bridge**:
```javascript
// Bridge.js
document.querySelectorAll('.pw-lazy').forEach(async (el) => {
  const payload = {
    type: el.dataset.type,
    settings: JSON.parse(el.dataset.settings)
  };

  const response = await fetch('/pw-api/render-widget', {
    method: 'POST',
    body: JSON.stringify(payload)
  });

  el.innerHTML = await response.text();
  el.classList.remove('is-loading');
});
```

### 4.3.3 Inheritance Rules

**Rule 1: Parent SSR → Child can be SSR or CSR**
```
Section (SSR)
├── Hero Widget (SSR) ✓
└── Carousel Widget (CSR) ✓
```

**Rule 2: Parent CSR → Child must be CSR**
```
Modal (CSR)
├── Form Widget (CSR) ✓
└── Text Widget (SSR) ✗ (Not allowed)
```

---

## 4.4 SPA Navigation

### 4.4.1 Bridge.js Implementation

```javascript
// assets/js/bridge.js
const PW_SPA = {
  init() {
    // Intercept link clicks
    document.addEventListener('click', e => {
      const link = e.target.closest('a');
      if (this.isInternalLink(link)) {
        e.preventDefault();
        this.navigateTo(link.href);
      }
    });

    // Handle browser back button
    window.onpopstate = () => this.navigateTo(window.location.href, false);
  },

  isInternalLink(link) {
    return link && 
           !link.hasAttribute('download') && 
           !link.target && 
           link.hostname === window.location.hostname;
  },

  async navigateTo(url, push = true) {
    // Check IndexedDB cache first
    const cached = await getFragment(url);
    
    if (cached) {
      // Display immediately (0ms latency)
      document.querySelector('#pw-app').innerHTML = cached;
      if (push) history.pushState(null, '', url);
      return;
    }

    // Fetch from server
    const response = await fetch(url, {
      headers: { 'X-Requested-With': 'PW-SPA' }
    });

    const html = await response.text();
    
    // Update DOM
    document.querySelector('#pw-app').innerHTML = html;
    
    // Cache for future
    await cacheFragment(url, html);
    
    if (push) history.pushState(null, '', url);
    
    // Reinitialize widgets
    this.reinitWidgets();
  },

  reinitWidgets() {
    // Re-run lazy widget loading
    document.querySelectorAll('.pw-lazy').forEach(this.loadWidget);
  }
};

PW_SPA.init();
```

### 4.4.2 Offline Support

```javascript
// Service Worker registration
if ('serviceWorker' in navigator) {
  navigator.serviceWorker.register('/sw.js')
    .then(reg => console.log('SW registered'))
    .catch(err => console.log('SW registration failed'));
}

// sw.js
self.addEventListener('fetch', event => {
  // Serve from IndexedDB if available
  if (event.request.mode === 'navigate') {
    event.respondWith(
      caches.match(event.request).then(response => {
        return response || fetch(event.request);
      })
    );
  }
});
```

---

## 4.5 Gutenberg (Fork) Integration

PrestoWorld fork repo [wordpress/gutenberg](https://github.com/WordPress/gutenberg) để dùng làm Page Builder — thay thế hoàn toàn Elementor 2. Gutenberg fork chạy độc lập với WordPress core, kết nối vào PrestoWorld thông qua Block API và PHP block renderer của Witals.

### 4.5.1 Fork Strategy

```
github.com/prestoworld/gutenberg (fork)
├── packages/           ← Giữ nguyên block editor packages
├── lib/                ← Bỏ WP REST API calls → thay bằng PW REST API
├── phplib/             ← Block renderer kết nối Witals
└── build/              ← Compiled JS/CSS assets
```

**Thay đổi so với upstream**:
- REST API endpoint: `/wp/v2/` → `/pw-api/v1/`
- Authentication: WP nonces → PrestoWorld nonces (`Nonce::create`)
- Block rendering: `render_callback` PHP → Witals service layer
- Post/Term queries: WP_Query → `PostRepository` / `TermRepository`
- Media: WP Media Library → PrestoWorld MediaService

### 4.5.2 Block Registration (PHP)

```php
namespace App\Blocks;

use PrestoWorld\Core\Blocks\BaseBlock;

class HeadingBlock extends BaseBlock
{
    public string $name = 'presto/heading';

    public function attributes(): array
    {
        return [
            'content' => ['type' => 'string', 'default' => ''],
            'level'   => ['type' => 'integer', 'default' => 2],
            'color'   => ['type' => 'string', 'default' => '#000000'],
        ];
    }

    public function render(array $attributes, string $content): string
    {
        $tag   = 'h' . (int) $attributes['level'];
        $color = \PrestoWorld\Core\Escape::attr($attributes['color']);
        $text  = \PrestoWorld\Core\Escape::html($attributes['content']);

        return "<{$tag} style=\"color:{$color}\">{$text}</{$tag}>";
    }
}
```

**Block Registry Bootloader**:
```php
namespace App\Bootloader;

use Witals\Boot\Bootloader\Bootloader;
use PrestoWorld\Core\Blocks\BlockRegistry;

class BlockBootloader extends Bootloader
{
    public function boot(BlockRegistry $registry): void
    {
        $registry->register(new \App\Blocks\HeadingBlock());
        $registry->register(new \App\Blocks\ImageBlock());
        $registry->register(new \App\Blocks\QueryLoopBlock());
        // Auto-discover blocks from plugins
        $registry->autoDiscover(directory('plugins'));
    }
}
```

### 4.5.3 REST API Bridge (Witals → Gutenberg Fork)

Gutenberg fork gọi PW REST API để fetch/save blocks. Controller xử lý phía PHP:

```php
namespace App\Controller\Api;

use Witals\Router\Annotation\Route;

class BlockController
{
    #[Route(route: '/pw-api/v1/blocks', methods: 'GET')]
    public function list(BlockRegistry $registry): array
    {
        return $registry->all();
    }

    #[Route(route: '/pw-api/v1/posts/<id>/blocks', methods: 'GET')]
    public function getPostBlocks(string $id, PostRepository $posts): array
    {
        $post = $posts->find((int) $id);
        return parse_blocks($post->content); // PW block parser
    }

    #[Route(route: '/pw-api/v1/posts/<id>/blocks', methods: 'POST')]
    public function savePostBlocks(string $id, Request $request, PostService $posts): array
    {
        $blocks = $request->input('blocks');
        $posts->saveBlocks((int) $id, $blocks);
        return ['success' => true];
    }
}
```

### 4.5.4 Gutenberg Fork Build

```json
// package.json (fork root)
{
  "scripts": {
    "build:presto": "PRESTO_API=/pw-api/v1 webpack --config webpack.presto.js",
    "dev:presto":   "PRESTO_API=/pw-api/v1 webpack --watch --config webpack.presto.js"
  }
}
```

Assets output vào `/public/assets/gutenberg/` và được enqueue qua `AssetManager`:

```php
AssetManager::enqueueScript('gutenberg-editor',
    '/assets/gutenberg/editor.js',
    ['wp-blocks', 'wp-editor', 'wp-components']
);
```

---

## 4.6 Error Handling in Frontend

### 4.6.1 Error Boundary

```tsx
export function ErrorBoundary(props: { children: any }) {
  const [error, setError] = createSignal<Error | null>(null);

  const handleError = (err: Error) => {
    setError(err);
    // Send to backend
    api.reportError(err.message);
  };

  return (
    <ErrorBoundary fallback={<ErrorFallback />}>
      {props.children}
    </ErrorBoundary>
  );
}

function ErrorFallback() {
  return (
    <div class="error-fallback">
      <h2>Something went wrong</h2>
      <button onClick={() => window.location.reload()}>
        Reload Page
      </button>
    </div>
  );
}
```

### 4.6.2 API Error Handling

```typescript
export async function apiRequest<T>(
  endpoint: string,
  options: RequestInit = {}
): Promise<T> {
  try {
    const response = await fetch(`/api/${endpoint}`, {
      ...options,
      headers: {
        'Content-Type': 'application/json',
        ...options.headers,
      },
    });

    if (!response.ok) {
      throw new Error(`HTTP ${response.status}: ${response.statusText}`);
    }

    return await response.json();
  } catch (error) {
    console.error('API Error:', error);
    // Show toast notification
    showToast('Request failed. Please try again.', 'error');
    throw error;
  }
}
```

---

## 4.7 Performance Optimization

### 4.7.1 Code Splitting

```typescript
// Lazy load heavy components
const PluginManager = lazy(() => import('./components/PluginManager'));

// Use in app
<Suspense fallback={<Loading />}>
  <PluginManager />
</Suspense>
```

### 4.7.2 Image Optimization

```typescript
export function OptimizedImage(props: { src: string; alt: string }) {
  return (
    <img 
      src={props.src} 
      alt={props.alt}
      loading="lazy"
      decoding="async"
      width="auto"
      height="auto"
    />
  );
}
```

### 4.7.3 CSS Optimization

```css
/* Critical CSS inline */
.critical-header {
  /* Header styles */
}

/* Lazy load other CSS */
@import url('/assets/css/dashboard.css');
```

---

## 4.8 Build Process

### 4.8.1 Vite Configuration

```typescript
// vite.config.ts
import { defineConfig } from 'vite';
import solidPlugin from 'vite-plugin-solid';

export default defineConfig({
  plugins: [solidPlugin()],
  build: {
    target: 'esnext',
    minify: 'terser',
    rollupOptions: {
      output: {
        manualChunks: {
          'vendor': ['solid-js', 'solid-js/web'],
          'utils': ['./src/utils/*'],
        },
      },
    },
  },
});
```

### 4.8.2 Build Script

```json
{
  "scripts": {
    "dev": "vite",
    "build": "vite build",
    "preview": "vite preview"
  }
}
```

---

## 4.9 Testing

### 4.9.1 Unit Testing

```typescript
import { render, screen } from 'solid-testing-library';
import { describe, it, expect } from 'vitest';
import PluginCard from './PluginCard';

describe('PluginCard', () => {
  it('renders plugin name', () => {
    render(() => <PluginCard name="Test Plugin" version="1.0" isActive={false} />);
    expect(screen.getByText('Test Plugin')).toBeInTheDocument();
  });

  it('toggles active state', async () => {
    const { getByText } = render(() => 
      <PluginCard name="Test Plugin" version="1.0" isActive={false} />
    );
    
    const button = getByText('Activate');
    button.click();
    
    expect(getByText('Deactivate')).toBeInTheDocument();
  });
});
```

### 4.9.2 E2E Testing

```typescript
import { test, expect } from '@playwright/test';

test('SPA navigation', async ({ page }) => {
  await page.goto('http://localhost:8080');
  
  // Click internal link
  await page.click('a[href="/plugins"]');
  
  // Should not reload page
  expect(await page.textContent('h1')).toBe('Plugins');
  
  // URL should change
  expect(page.url()).toContain('/plugins');
});
```
