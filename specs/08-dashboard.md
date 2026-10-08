# 08. Dashboard - SPA Dashboard UI

## 8.1 Dashboard Architecture

### 8.1.1 Technology Stack

- **Framework**: SolidJS + TSX
- **State Management**: SolidJS Signals + IndexedDB
- **Routing**: Solid Router
- **Styling**: Tailwind CSS (or custom CSS)
- **Icons**: Lucide or Heroicons (SVG)
- **Build Tool**: Vite

### 8.1.2 Project Structure

```
/public/assets/src/
├── /components
│   ├── /layout
│   │   ├── Sidebar.tsx
│   │   ├── Header.tsx
│   │   └── Layout.tsx
│   ├── /dashboard
│   │   ├── Overview.tsx
│   │   ├── PluginManager.tsx
│   │   ├── DatabaseMonitor.tsx
│   │   └── ErrorReports.tsx
│   ├── /plugins
│   │   ├── PluginCard.tsx
│   │   ├── PluginList.tsx
│   │   └── HookLedger.tsx
│   └── /shared
│       ├── Button.tsx
│       ├── Card.tsx
│       ├── Modal.tsx
│       └── Toast.tsx
├── /stores
│   ├── pluginStore.ts
│   ├── databaseStore.ts
│   ├── settingsStore.ts
│   └── uiStore.ts
├── /utils
│   ├── api.ts
│   ├── indexedDB.ts
│   └── helpers.ts
├── App.tsx
└── index.tsx
```

---

## 8.2 State Management

### 8.2.1 Plugin Store

```typescript
// stores/pluginStore.ts
import { createSignal, createStore } from 'solid-js';

interface Plugin {
  id: string;
  name: string;
  version: string;
  isActive: boolean;
  isNative: boolean;
  hookCount: number;
  compatibilityScore: number;
}

export const [plugins, setPlugins] = createSignal<Plugin[]>([]);
export const [loading, setLoading] = createSignal(false);
export const [error, setError] = createSignal<string | null>(null);

export async function loadPlugins() {
  setLoading(true);
  try {
    const response = await api.get('/api/plugins');
    setPlugins(response.data);
  } catch (err) {
    setError('Failed to load plugins');
  } finally {
    setLoading(false);
  }
}

export async function togglePlugin(id: string) {
  try {
    await api.post(`/api/plugins/${id}/toggle`);
    
    // Update local state
    setPlugins(prev => prev.map(p => 
      p.id === id ? { ...p, isActive: !p.isActive } : p
    ));
    
    // Sync to IndexedDB
    await db.put('plugins', await api.get(`/api/plugins/${id}`));
  } catch (err) {
    setError('Failed to toggle plugin');
  }
}
```

### 8.2.2 Database Store

```typescript
// stores/databaseStore.ts
import { createSignal } from 'solid-js';

interface DatabaseStats {
  postgres: {
    status: string;
    version: string;
    connections: number;
    size: string;
  };
  sqlite: {
    status: string;
    size: string;
    wal_size: string;
    integrity: string;
  };
}

export const [dbStats, setDbStats] = createSignal<DatabaseStats>({
  postgres: { status: 'unknown', version: '', connections: 0, size: '0 B' },
  sqlite: { status: 'unknown', size: '0 B', wal_size: '0 B', integrity: 'unknown' },
});

export async function loadDatabaseStats() {
  const response = await api.get('/api/database/stats');
  setDbStats(response.data);
}
```

---

## 8.3 Main Layout

### 8.3.1 Layout Component

```tsx
// components/layout/Layout.tsx
import { Sidebar } from './Sidebar';
import { Header } from './Header';

export function Layout(props: { children: any }) {
  return (
    <div class="layout">
      <Sidebar />
      <div class="main-content">
        <Header />
        <main class="content">
          {props.children}
        </main>
      </div>
    </div>
  );
}
```

### 8.3.2 Sidebar Component

```tsx
// components/layout/Sidebar.tsx
import { A } from '@solidjs/router';

export function Sidebar() {
  const [currentPath, setCurrentPath] = createSignal('/dashboard');

  const menuItems = [
    { path: '/dashboard', label: 'Overview', icon: 'LayoutDashboard' },
    { path: '/plugins', label: 'Plugins', icon: 'Package' },
    { path: '/database', label: 'Database', icon: 'Database' },
    { path: '/errors', label: 'Error Reports', icon: 'AlertTriangle' },
    { path: '/settings', label: 'Settings', icon: 'Settings' },
  ];

  return (
    <aside class="sidebar">
      <div class="logo">
        <h1>PrestoWorld</h1>
      </div>
      <nav>
        <For each={menuItems}>
          {(item) => (
            <A
              href={item.path}
              classList={() => [
                'nav-item',
                currentPath() === item.path ? 'active' : ''
              ]}
            >
              <Icon name={item.icon} />
              <span>{item.label}</span>
            </A>
          )}
        </For>
      </nav>
      <div class="sidebar-footer">
        <small>v1.0.0</small>
      </div>
    </aside>
  );
}
```

---

## 8.4 Plugin Management

### 8.4.1 Plugin Manager Component

```tsx
// components/dashboard/PluginManager.tsx
import { plugins, loading, togglePlugin } from '../../stores/pluginStore';
import { SwitchMode } from './SwitchMode';

export function PluginManager() {
  const [viewMode, setViewMode] = createSignal<'grid' | 'list'>('grid');
  const [filter, setFilter] = createSignal<'all' | 'native' | 'legacy'>('all');

  const filteredPlugins = createMemo(() => {
    const all = plugins();
    if (filter() === 'native') {
      return all.filter(p => p.isNative);
    }
    if (filter() === 'legacy') {
      return all.filter(p => !p.isNative);
    }
    return all;
  });

  return (
    <div class="plugin-manager">
      <div class="toolbar">
        <div class="filters">
          <button 
            classList={() => ['filter-btn', filter() === 'all' ? 'active' : '']}
            onClick={() => setFilter('all')}
          >
            All
          </button>
          <button 
            classList={() => ['filter-btn', filter() === 'native' ? 'active' : '']}
            onClick={() => setFilter('native')}
          >
            Native
          </button>
          <button 
            classList={() => ['filter-btn', filter() === 'legacy' ? 'active' : '']}
            onClick={() => setFilter('legacy')}
          >
            Legacy
          </button>
        </div>
        <div class="view-toggle">
          <button onClick={() => setViewMode('grid')}>
            <Icon name="Grid" />
          </button>
          <button onClick={() => setViewMode('list')}>
            <Icon name="List" />
          </button>
        </div>
      </div>

      <Show when={loading()}>
        <div class="loading">Loading plugins...</div>
      </Show>

      <div classList={() => [
        'plugin-container',
        viewMode() === 'grid' ? 'grid-view' : 'list-view'
      ]}>
        <For each={filteredPlugins()}>
          {(plugin) => (
            <PluginCard 
              plugin={plugin} 
              onToggle={() => togglePlugin(plugin.id)}
            />
          )}
        </For>
      </div>
    </div>
  );
}
```

### 8.4.2 Plugin Card Component

```tsx
// components/plugins/PluginCard.tsx
import { Icon } from './Icon';

interface PluginCardProps {
  plugin: Plugin;
  onToggle: () => void;
}

export function PluginCard(props: PluginCardProps) {
  const [showDetails, setShowDetails] = createSignal(false);

  return (
    <div class="plugin-card">
      <div class="card-header">
        <div class="plugin-icon">
          <img src={props.plugin.icon} alt={props.plugin.name} />
        </div>
        <div class="plugin-info">
          <h3>{props.plugin.name}</h3>
          <span class="version">v{props.plugin.version}</span>
          <Show when={props.plugin.isNative}>
            <span class="badge native">Native</span>
          </Show>
          <Show when={!props.plugin.isNative}>
            <span class="badge legacy">Legacy</span>
          </Show>
        </div>
      </div>

      <div class="card-body">
        <div class="stats">
          <div class="stat">
            <span class="label">Hooks:</span>
            <span class="value">{props.plugin.hookCount}</span>
          </div>
          <div class="stat">
            <span class="label">Score:</span>
            <span class="value">{props.plugin.compatibilityScore}%</span>
          </div>
        </div>
      </div>

      <div class="card-footer">
        <Switch 
          checked={props.plugin.isActive}
          onChange={props.onToggle}
        />
        <button onClick={() => setShowDetails(!showDetails())}>
          {showDetails() ? 'Hide Details' : 'Details'}
        </button>
      </div>

      <Show when={showDetails()}>
        <div class="details-panel">
          <h4>Compiler Report</h4>
          <CompilerReport pluginId={props.plugin.id} />

          <h4>Hook Ledger</h4>
          <HookLedger pluginId={props.plugin.id} />
        </div>
      </Show>
    </div>
  );
}
```

### 8.4.3 Hook Ledger Component

```tsx
// components/plugins/HookLedger.tsx
interface HookLedgerProps {
  pluginId: string;
}

export function HookLedger(props: HookLedgerProps) {
  const [hooks, setHooks] = createSignal([]);

  onMount(async () => {
    const response = await api.get(`/api/plugins/${props.pluginId}/hooks`);
    setHooks(response.data);
  });

  return (
    <div class="hook-ledger">
      <table>
        <thead>
          <tr>
            <th>Tag</th>
            <th>Type</th>
            <th>Priority</th>
            <th>Callback</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          <For each={hooks()}>
            {(hook) => (
              <tr>
                <td>{hook.tag}</td>
                <td>{hook.hook_type}</td>
                <td>{hook.priority}</td>
                <td class="callback">{hook.callback_data}</td>
                <td>
                  <Switch 
                    checked={hook.is_active}
                    onChange={(checked) => toggleHook(hook.id, checked)}
                  />
                </td>
              </tr>
            )}
          </For>
        </tbody>
      </table>
    </div>
  );
}
```

### 8.4.4 Compiler Report Component

> Spec chi tiết: [10.8 Compiler Report](./10-wordpress-compiler.md)

```tsx
// components/plugins/CompilerReport.tsx
interface CompilerReportProps {
  pluginId: string;
}

export function CompilerReport(props: CompilerReportProps) {
  const [report, setReport] = createSignal<CompileReport | null>(null);
  const [compiling, setCompiling] = createSignal(false);

  onMount(async () => {
    const response = await api.get(`/api/plugins/${props.pluginId}/compile-report`);
    setReport(response.data);
  });

  const runCompile = async () => {
    setCompiling(true);
    await api.post(`/api/plugins/${props.pluginId}/compile`);
    const response = await api.get(`/api/plugins/${props.pluginId}/compile-report`);
    setReport(response.data);
    setCompiling(false);
  };

  return (
    <div class="compiler-report">
      <Show when={report()} fallback={<div class="loading">No compile data</div>}>
        {(r) => (
          <>
            <div class="stats">
              <span class="badge" classList={{ compiled: r().mode === 'compiled', shimmed: r().mode === 'shimmed' }}>
                {r().mode === 'compiled' ? 'Compiled' : 'Shimmed'}
              </span>
              <span class="score">Score: {r().compatibility_score}%</span>
              <span>Functions: {r().stats.functions_mapped}</span>
              <span>Classes: {r().stats.classes_mapped}</span>
              <span>Queries: {r().stats.queries_transformed}</span>
            </div>

            <Show when={r().issues.length > 0}>
              <ul class="issues">
                <For each={r().issues}>
                  {(issue) => (
                    <li classList={{ [issue.type]: true }}>
                      {issue.symbol ?? issue.file}:{issue.line ?? ''} — {issue.type}
                    </li>
                  )}
                </For>
              </ul>
            </Show>

            <button onClick={runCompile} disabled={compiling()}>
              {compiling() ? 'Compiling…' : 'Run Compile'}
            </button>
          </>
        )}
      </Show>
    </div>
  );
}
```

---

## 8.5 Database Monitor

### 8.5.1 Database Monitor Component

```tsx
// components/dashboard/DatabaseMonitor.tsx
import { dbStats, loadDatabaseStats } from '../../stores/databaseStore';

export function DatabaseMonitor() {
  onMount(() => {
    loadDatabaseStats();
    
    // Refresh every 30 seconds
    const interval = setInterval(loadDatabaseStats, 30000);
    onCleanup(() => clearInterval(interval));
  });

  return (
    <div class="database-monitor">
      <h2>Database Status</h2>
      
      <div class="db-section">
        <h3>PostgreSQL (Core)</h3>
        <div class="stat-grid">
          <div class="stat-card">
            <span class="label">Status</span>
            <span classList={() => ['value', dbStats().postgres.status === 'healthy' ? 'healthy' : 'unhealthy']}>
              {dbStats().postgres.status}
            </span>
          </div>
          <div class="stat-card">
            <span class="label">Version</span>
            <span class="value">{dbStats().postgres.version}</span>
          </div>
          <div class="stat-card">
            <span class="label">Connections</span>
            <span class="value">{dbStats().postgres.connections}</span>
          </div>
          <div class="stat-card">
            <span class="label">Size</span>
            <span class="value">{dbStats().postgres.size}</span>
          </div>
        </div>
      </div>

      <div class="db-section">
        <h3>SQLite (Legacy)</h3>
        <div class="stat-grid">
          <div class="stat-card">
            <span class="label">Status</span>
            <span classList={() => ['value', dbStats().sqlite.status === 'ok' ? 'healthy' : 'unhealthy']}>
              {dbStats().sqlite.status}
            </span>
          </div>
          <div class="stat-card">
            <span class="label">Size</span>
            <span class="value">{dbStats().sqlite.size}</span>
          </div>
          <div class="stat-card">
            <span class="label">WAL Size</span>
            <span class="value">{dbStats().sqlite.wal_size}</span>
          </div>
          <div class="stat-card">
            <span class="label">Integrity</span>
            <span class="value">{dbStats().sqlite.integrity}</span>
          </div>
        </div>
      </div>

      <div class="db-actions">
        <button onClick={() => backupDatabase()}>Backup to R2</button>
        <button onClick={() => optimizeDatabase()}>Optimize</button>
      </div>
    </div>
  );
}

async function backupDatabase() {
  await api.post('/api/database/backup');
  showToast('Database backed up to Cloudflare R2');
}

async function optimizeDatabase() {
  await api.post('/api/database/optimize');
  showToast('Database optimized');
  loadDatabaseStats();
}
```

---

## 8.6 Error Reports

### 8.6.1 Error Reports Component

```tsx
// components/dashboard/ErrorReports.tsx
export function ErrorReports() {
  const [reports, setReports] = createSignal([]);
  const [loading, setLoading] = createSignal(false);

  onMount(async () => {
    setLoading(true);
    try {
      const response = await api.get('/api/error-reports');
      setReports(response.data);
    } finally {
      setLoading(false);
    }
  });

  return (
    <div class="error-reports">
      <h2>Error Reports</h2>
      
      <Show when={loading()}>
        <div class="loading">Loading reports...</div>
      </Show>

      <div class="reports-list">
        <For each={reports()}>
          {(report) => (
            <div class="report-card">
              <div class="report-header">
                <span class="plugin">{report.plugin}</span>
                <span class="date">{new Date(report.timestamp).toLocaleString()}</span>
              </div>
              <div class="report-body">
                <pre>{report.error_message}</pre>
              </div>
              <div class="report-footer">
                <span class="status">{report.status}</span>
                <button onClick={() => viewDetails(report)}>View Details</button>
              </div>
            </div>
          )}
        </For>
      </div>
    </div>
  );
}
```

---

## 8.7 Settings

### 8.7.1 Settings Component

```tsx
// components/dashboard/Settings.tsx
export function Settings() {
  const [uiMode, setUiMode] = createSignal<'spa' | 'classic'>('spa');
  const [autoReport, setAutoReport] = createSignal(true);

  return (
    <div class="settings">
      <h2>Settings</h2>
      
      <div class="settings-section">
        <h3>Dashboard Mode</h3>
        <div class="setting-item">
          <label>
            <input 
              type="radio" 
              value="spa" 
              checked={uiMode() === 'spa'}
              onInput={() => setUiMode('spa')}
            />
            SPA Mode (Modern)
          </label>
          <label>
            <input 
              type="radio" 
              value="classic" 
              checked={uiMode() === 'classic'}
              onInput={() => setUiMode('classic')}
            />
            Classic Mode (WordPress Compatible)
          </label>
        </div>
      </div>

      <div class="settings-section">
        <h3>Error Reporting</h3>
        <div class="setting-item">
          <label>
            <input 
              type="checkbox" 
              checked={autoReport()}
              onInput={(e) => setAutoReport(e.currentTarget.checked)}
            />
            Automatically send error reports to PrestoWorld team
          </label>
        </div>
      </div>

      <div class="settings-actions">
        <button onClick={() => saveSettings()}>Save Settings</button>
      </div>
    </div>
  );
}

async function saveSettings() {
  await api.post('/api/settings', {
    ui_mode: uiMode(),
    auto_report: autoReport(),
  });
  showToast('Settings saved');
}
```

---

## 8.8 Switch Mode (SPA ↔ Classic)

### 8.8.1 SwitchMode Component

```tsx
// components/dashboard/SwitchMode.tsx
export function SwitchMode() {
  const [mode, setMode] = createSignal<'spa' | 'classic'>('spa');

  return (
    <div class="switch-mode">
      <span class="label">Dashboard Mode:</span>
      <button 
        classList={() => ['mode-btn', mode() === 'spa' ? 'active' : '']}
        onClick={() => switchTo('spa')}
      >
        SPA
      </button>
      <button 
        classList={() => ['mode-btn', mode() === 'classic' ? 'active' : '']}
        onClick={() => switchTo('classic')}
      >
        Classic
      </button>
    </div>
  );
}

async function switchTo(newMode: 'spa' | 'classic') {
  await api.post('/api/settings/mode', { mode: newMode });
  
  // Reload page
  window.location.reload();
}
```

---

## 8.9 UI Components

### 8.9.1 Button Component

```tsx
// components/shared/Button.tsx
interface ButtonProps {
  onClick: () => void;
  variant?: 'primary' | 'secondary' | 'danger';
  children: any;
}

export function Button(props: ButtonProps) {
  return (
    <button 
      classList={() => [
        'btn',
        `btn-${props.variant || 'primary'}`
      ]}
      onClick={props.onClick}
    >
      {props.children}
    </button>
  );
}
```

### 8.9.2 Modal Component

```tsx
// components/shared/Modal.tsx
interface ModalProps {
  isOpen: boolean;
  onClose: () => void;
  title: string;
  children: any;
}

export function Modal(props: ModalProps) {
  return (
    <Show when={props.isOpen()}>
      <div class="modal-overlay" onClick={props.onClose}>
        <div class="modal" onClick={(e) => e.stopPropagation()}>
          <div class="modal-header">
            <h3>{props.title}</h3>
            <button class="close-btn" onClick={props.onClose}>×</button>
          </div>
          <div class="modal-body">
            {props.children}
          </div>
        </div>
      </div>
    </Show>
  );
}
```

### 8.9.3 Toast Component

```tsx
// components/shared/Toast.tsx
const [toasts, setToasts] = createSignal<Array<{id: string; message: string; type: 'success' | 'error'}>>([]);

export function showToast(message: string, type: 'success' | 'error' = 'success') {
  const id = Date.now().toString();
  setToasts(prev => [...prev, { id, message, type }]);
  
  setTimeout(() => {
    setToasts(prev => prev.filter(t => t.id !== id));
  }, 3000);
}

export function ToastContainer() {
  return (
    <div class="toast-container">
      <For each={toasts()}>
        {(toast) => (
          <div classList={() => ['toast', `toast-${toast.type}`]}>
            {toast.message}
          </div>
        )}
      </For>
    </div>
  );
}
```

---

## 8.10 Responsive Design

### 8.10.1 Mobile Support

```css
/* assets/css/dashboard.css */
.sidebar {
  width: 250px;
  transition: transform 0.3s ease;
}

@media (max-width: 768px) {
  .sidebar {
    position: fixed;
    left: -250px;
    z-index: 1000;
  }
  
  .sidebar.open {
    transform: translateX(250px);
  }
  
  .main-content {
    margin-left: 0;
  }
  
  .plugin-container.grid-view {
    grid-template-columns: 1fr;
  }
}

@media (min-width: 769px) {
  .plugin-container.grid-view {
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  }
}
```

---

## 8.11 Performance Optimization

### 8.11.1 Code Splitting

```typescript
// Lazy load heavy components
const DatabaseMonitor = lazy(() => import('./components/dashboard/DatabaseMonitor'));
const ErrorReports = lazy(() => import('./components/dashboard/ErrorReports'));

// In router
<Suspense fallback={<LoadingSpinner />}>
  <Routes>
    <Route path="/database" component={DatabaseMonitor} />
    <Route path="/errors" component={ErrorReports} />
  </Routes>
</Suspense>
```

### 8.11.2 Image Optimization

```tsx
export function OptimizedImage(props: { src: string; alt: string; class?: string }) {
  return (
    <img 
      src={props.src}
      alt={props.alt}
      class={props.class}
      loading="lazy"
      decoding="async"
      width="auto"
      height="auto"
    />
  );
}
```

---

## 8.12 Accessibility

### 8.12.1 ARIA Labels

```tsx
<button 
  aria-label="Toggle plugin"
  aria-pressed={props.plugin.isActive}
  onClick={props.onToggle}
>
  <Icon name={props.plugin.isActive ? 'Check' : 'X'} />
</button>
```

### 8.12.2 Keyboard Navigation

```typescript
export function KeyboardNav() {
  const [focusedIndex, setFocusedIndex] = createSignal(0);

  const handleKeyDown = (e: KeyboardEvent) => {
    if (e.key === 'ArrowDown') {
      setFocusedIndex(prev => prev + 1);
    } else if (e.key === 'ArrowUp') {
      setFocusedIndex(prev => Math.max(0, prev - 1));
    } else if (e.key === 'Enter') {
      // Activate focused item
    }
  };

  return (
    <div class="keyboard-nav" onKeyDown={handleKeyDown} tabIndex={0}>
      {/* Items */}
    </div>
  );
}
```
