# 06. Legacy Support - WordPress.org Compatibility

## 6.1 Overview

**Objective**: Provide seamless compatibility with WordPress.org plugins while maintaining PrestoWorld's performance and security.

**Strategy**: 
- **Sandbox**: Isolate legacy plugins in SQLite
- **Bridge**: Translate WordPress APIs to PrestoWorld equivalents
- **Registry**: Centralized hook management in SQLite
- **Transformer**: Convert MySQL queries to PostgreSQL/SQLite ([10.3](./10-wordpress-compiler.md))
- **Function Transformer**: ~390 hàm WP → PW services (shim runtime + AST rewrite → [10.4](./10-wordpress-compiler.md))
- **Class Transformer**: 50+ class WP → PW classes (shim/rewrite/structural → [10.5](./10-wordpress-compiler.md))
- **Compiler**: Pipeline compile mã nguồn WP sang PW-native → [10](./10-wordpress-compiler.md)

---

## 6.2 WordPress API Compatibility

### 6.2.1 Global Functions

> **Danh sách đầy đủ ~390 hàm WP được map**: [10.4 Function Transformer](./10-wordpress-compiler.md).
> Section này chỉ nêu cơ chế shim cho hooks.

**add_action / do_action**:
```php
// app/Core/Legacy/wp-compatibility.php

function add_action(string $tag, callable $callback, int $priority = 10, int $accepted_args = 1) {
    $registry = \Spiral\Core\ContainerScope::getContainer()->get(\PrestoWorld\Core\Legacy\LegacyRegistry::class);
    
    $registry->recordHook([
        'hook_type' => 'action',
        'tag' => $tag,
        'callback' => $callback,
        'priority' => $priority,
        'accepted_args' => $accepted_args,
    ]);
}

function do_action(string $tag, ...$args) {
    $invoker = \Spiral\Core\ContainerScope::getContainer()->get(\PrestoWorld\Core\Legacy\LegacyInvoker::class);
    return $invoker->trigger($tag, $args);
}
```

**add_filter / apply_filters**:
```php
function add_filter(string $tag, callable $callback, int $priority = 10, int $accepted_args = 1) {
    $registry = \Spiral\Core\ContainerScope::getContainer()->get(\PrestoWorld\Core\Legacy\LegacyRegistry::class);
    
    $registry->recordHook([
        'hook_type' => 'filter',
        'tag' => $tag,
        'callback' => $callback,
        'priority' => $priority,
        'accepted_args' => $accepted_args,
    ]);
}

function apply_filters(string $tag, mixed $value, ...$args) {
    $invoker = \Spiral\Core\ContainerScope::getContainer()->get(\PrestoWorld\Core\Legacy\LegacyInvoker::class);
    return $invoker->trigger($tag, [$value, ...$args]);
}
```

### 6.2.2 Global Variables

**$wpdb**:
```php
global $wpdb;
$wpdb = \Spiral\Core\ContainerScope::getContainer()->get(\PrestoWorld\Core\Database\PrestoWpdb::class);
```

**$wp_query**:
```php
global $wp_query;
$wp_query = new \PrestoWorld\Core\Legacy\WPQuery();
```

### 6.2.3 Constants

```php
// Define WordPress constants
define('WP_DEBUG', env('WP_DEBUG', false));
define('WP_CONTENT_DIR', directory('storage') . '/wp-content');
define('WP_PLUGIN_DIR', WP_CONTENT_DIR . '/plugins');
define('WP_THEME_DIR', WP_CONTENT_DIR . '/themes');
```

---

## 6.3 SQLite Registry System

### 6.3.1 LegacyRegistry Implementation

```php
namespace PrestoWorld\Core\Legacy;

use PDO;

class LegacyRegistry
{
    private PDO $db;

    public function __construct(string $dbPath) {
        $this->db = new PDO("sqlite:$dbPath");
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Optimize SQLite
        $this->db->exec("PRAGMA journal_mode = WAL;");
        $this->db->exec("PRAGMA synchronous = NORMAL;");
    }

    public function recordHook(array $data): void
    {
        // Get source file from backtrace
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        $sourceFile = $trace[1]['file'] ?? 'unknown';

        // Analyze callback
        $callbackInfo = $this->analyzeCallback($data['callback']);
        
        // Analyze IoC requirements
        $iocMeta = $this->analyzeIoC($data['callback']);

        $stmt = $this->db->prepare("
            INSERT INTO legacy_hooks 
            (tag, hook_type, callback_type, callback_data, priority, accepted_args, source_file, ioc_meta)
            VALUES (:tag, :type, :cb_type, :cb_data, :priority, :args, :source, :ioc)
        ");

        $stmt->execute([
            ':tag' => $data['tag'],
            ':type' => $data['hook_type'],
            ':cb_type' => $callbackInfo['type'],
            ':cb_data' => $callbackInfo['data'],
            ':priority' => $data['priority'],
            ':args' => $data['accepted_args'],
            ':source' => $sourceFile,
            ':ioc' => json_encode($iocMeta),
        ]);
    }

    private function analyzeCallback($callback): array
    {
        if (is_string($callback)) {
            return ['type' => 'function', 'data' => $callback];
        }
        if (is_array($callback)) {
            return ['type' => 'class_method', 'data' => implode('@', $callback)];
        }
        if ($callback instanceof \Closure) {
            return ['type' => 'closure', 'data' => 'anonymous'];
        }
        return ['type' => 'unknown', 'data' => ''];
    }

    private function analyzeIoC($callback): array
    {
        // Use Reflection to determine Service dependencies
        $reflection = is_array($callback) 
            ? new \ReflectionMethod($callback[0], $callback[1])
            : new \ReflectionFunction($callback);

        $meta = [];
        foreach ($reflection->getParameters() as $index => $param) {
            $type = $param->getType();
            if ($type && !$type->isBuiltin()) {
                $meta[$index] = [
                    'type' => 'service',
                    'class' => $type->getName(),
                ];
            } else {
                $meta[$index] = [
                    'type' => 'context',
                    'name' => $param->getName(),
                ];
            }
        }
        return $meta;
    }
}
```

### 6.3.2 Entity Management

```php
public function registerPlugin(string $slug, string $path, string $type = 'plugin'): int
{
    $stmt = $this->db->prepare("
        INSERT INTO legacy_entities (slug, path, type, is_active)
        VALUES (:slug, :path, :type, 1)
    ");
    
    $stmt->execute([
        ':slug' => $slug,
        ':path' => $path,
        ':type' => $type,
    ]);
    
    return $this->db->lastInsertId();
}

public function activatePlugin(int $entityId): void
{
    $stmt = $this->db->prepare("
        UPDATE legacy_entities SET is_active = 1 WHERE id = :id
    ");
    $stmt->execute([':id' => $entityId]);
}

public function deactivatePlugin(int $entityId): void
{
    $stmt = $this->db->prepare("
        UPDATE legacy_entities SET is_active = 0 WHERE id = :id
    ");
    $stmt->execute([':id' => $entityId]);
    
    // Cascade delete hooks
    // SQLite handles this via FOREIGN KEY ON DELETE CASCADE
}
```

---

## 6.4 Legacy Invoker

### 6.4.1 Hook Execution

```php
namespace PrestoWorld\Core\Legacy;

use PDO;
use Spiral\Core\Container;

class LegacyInvoker
{
    public function __construct(
        private PDO $db,
        private Container $container
    ) {}

    public function trigger(string $tag, array $hookArgs = []): mixed
    {
        // Query hooks from SQLite
        $stmt = $this->db->prepare("
            SELECT * FROM legacy_hooks 
            WHERE tag = :tag 
            ORDER BY priority ASC
        ");
        $stmt->execute([':tag' => $tag]);
        $hooks = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $result = $hookArgs[0] ?? null; // For filters

        foreach ($hooks as $hook) {
            // Lazy load source file
            if (file_exists($hook['source_file'])) {
                include_once $hook['source_file'];
            }

            // Resolve callback
            $callback = $this->resolveCallback($hook);
            if (!$callback) continue;

            // Resolve arguments with IoC
            $finalArgs = $this->resolveArguments($callback, $hookArgs, json_decode($hook['ioc_meta'], true));

            // Execute
            $currentResult = call_user_func_array($callback, $finalArgs);

            // For filters: pass result to next hook
            if ($hook['hook_type'] === 'filter') {
                $hookArgs[0] = $currentResult;
                $result = $currentResult;
            }
        }

        return $result;
    }

    private function resolveCallback($hook): ?callable
    {
        if ($hook['callback_type'] === 'function') {
            return $hook['callback_data'];
        }
        if ($hook['callback_type'] === 'class_method') {
            [$class, $method] = explode('@', $hook['callback_data']);
            return [$this->container->get($class), $method];
        }
        // Closures require special handling
        return null;
    }

    private function resolveArguments($callback, $hookArgs, $iocMeta): array
    {
        $reflection = is_array($callback) 
            ? new \ReflectionMethod($callback[0], $callback[1])
            : new \ReflectionFunction($callback);

        $params = $reflection->getParameters();
        $resolved = [];

        foreach ($params as $index => $param) {
            $type = $param->getType();
            
            // If marked as Service in IoC metadata
            if (isset($iocMeta[$index]) && $iocMeta[$index]['type'] === 'service') {
                $resolved[] = $this->container->get($iocMeta[$index]['class']);
            }
            // Otherwise, use hook arguments (positional)
            elseif (isset($hookArgs[$index])) {
                $resolved[] = $hookArgs[$index];
            } else {
                $resolved[] = $param->isDefaultValueAvailable() 
                    ? $param->getDefaultValue() 
                    : null;
            }
        }

        return $resolved;
    }
}
```

---

## 6.5 Plugin Activation System

### 6.5.1 Plugin Activator

```php
namespace PrestoWorld\Core\Legacy;

class PluginActivator
{
    private LegacyRegistry $registry;

    public function __construct(LegacyRegistry $registry) {
        $this->registry = $registry;
    }

    public function activate(string $pluginPath): void
    {
        // Register plugin in SQLite
        $entityId = $this->registry->registerPlugin(
            basename($pluginPath),
            $pluginPath
        );

        // Load plugin file in isolated environment
        $this->loadPluginFile($pluginPath);

        // Scan for hooks that were registered
        // They're already in SQLite via add_action/add_filter calls
    }

    private function loadPluginFile(string $path): void
    {
        // Define sandboxed environment
        $sandbox = $this->createSandbox();

        // Load plugin
        require_once $path;
    }

    private function createSandbox(): array
    {
        // Provide WordPress globals
        global $wpdb, $wp_query, $post;
        
        return [
            'wpdb' => $GLOBALS['wpdb'],
            'wp_query' => $GLOBALS['wp_query'],
        ];
    }
}
```

### 6.5.2 Hook Discovery

```php
class HookDiscovery
{
    public function scanPlugin(string $pluginPath): array
    {
        $content = file_get_contents($pluginPath);
        
        // Simple regex scan for add_action/add_filter
        preg_match_all('/add_(action|filter)\s*\(\s*[\'"]([^\'"]+)[\'"]\s*,/i', $content, $matches);
        
        $hooks = [];
        foreach ($matches[2] as $hook) {
            $hooks[] = $hook;
        }
        
        return array_unique($hooks);
    }
}
```

---

## 6.6 Error Handling for Legacy Plugins

### 6.6.1 Error Interceptor

```php
class LegacyErrorInterceptor
{
    private DataMasker $masker;
    private ErrorReporter $reporter;

    public function handle(\Throwable $e, string $pluginSlug): void
    {
        // Mask sensitive data
        $maskedMessage = $this->masker->mask($e->getMessage());
        
        // Log error
        error_log("Legacy plugin error [$pluginSlug]: $maskedMessage");
        
        // Send report to Cloudflare
        $this->reporter->send([
            'plugin' => $pluginSlug,
            'error' => $maskedMessage,
            'trace' => $e->getTraceAsString(),
        ]);
    }
}
```

### 6.6.2 Silent Fallback

```php
class SilentFallback
{
    private array $nonCriticalHooks = [
        'wp_head',
        'wp_footer',
        'wp_enqueue_scripts',
    ];

    public function shouldFailSilently(string $hook): bool
    {
        return in_array($hook, $this->nonCriticalHooks);
    }
}
```

---

## 6.7 Conflict Detection

### 6.7.1 Hook Conflict Detector

```php
class ConflictDetector
{
    public function detectDuplicateHooks(PDO $db): array
    {
        $stmt = $db->query("
            SELECT tag, COUNT(*) as count 
            FROM legacy_hooks 
            GROUP BY tag 
            HAVING count > 1
        ");
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function detectPriorityConflicts(PDO $db): array
    {
        $stmt = $db->query("
            SELECT tag, priority, COUNT(*) as count
            FROM legacy_hooks
            GROUP BY tag, priority
            HAVING count > 1
        ");
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
```

### 6.7.2 Core Hook Protection

```php
class CoreHookProtector
{
    private array $protectedHooks = [
        'presto_core_init',
        'presto_database_init',
    ];

    public function isProtected(string $hook): bool
    {
        return in_array($hook, $this->protectedHooks);
    }

    public function preventOverride(string $hook): bool
    {
        if ($this->isProtected($hook)) {
            error_log("Attempt to override protected hook: $hook");
            return true;
        }
        return false;
    }
}
```

---

## 6.8 Migration from WordPress

### 6.8.1 Database Migration

```php
class WordPressMigrator
{
    private $wpdb;
    private $prestoWpdb;

    public function migrateDatabase(string $wpConfigPath): void
    {
        // Load WordPress config
        require_once $wpConfigPath;
        
        // Connect to WordPress database
        $this->wpdb = new \wpdb(DB_USER, DB_PASSWORD, DB_NAME, DB_HOST);
        
        // Migrate core tables
        $this->migrateUsers();
        $this->migratePosts();
        $this->migrateOptions();
    }

    private function migrateUsers(): void
    {
        $users = $this->wpdb->get_results("SELECT * FROM wp_users");
        
        foreach ($users as $user) {
            $this->prestoWpdb->insert('pw_users', [
                'email' => $user->user_email,
                'display_name' => $user->display_name,
                'password_hash' => $user->user_pass,
                'role' => $this->mapRole($user->user_level),
            ]);
        }
    }

    private function mapRole(int $wpLevel): string
    {
        $map = [
            10 => 'administrator',
            7 => 'editor',
            2 => 'author',
            1 => 'contributor',
            0 => 'subscriber',
        ];
        
        return $map[$wpLevel] ?? 'subscriber';
    }
}
```

### 6.8.2 File Migration

```php
class FileMigrator
{
    public function migrateContent(string $wpContentDir, string $pwContentDir): void
    {
        // Migrate uploads
        $this->migrateUploads("$wpContentDir/uploads", "$pwContentDir/uploads");
        
        // Migrate plugins
        $this->migratePlugins("$wpContentDir/plugins", "$pwContentDir/plugins");
        
        // Migrate themes
        $this->migrateThemes("$wpContentDir/themes", "$pwContentDir/themes");
    }

    private function migrateUploads(string $source, string $dest): void
    {
        if (!is_dir($dest)) {
            mkdir($dest, 0755, true);
        }
        
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($source)
        );

        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $relativePath = substr($file->getPathname(), strlen($source));
                copy($file->getPathname(), $dest . $relativePath);
            }
        }
    }
}
```

---

## 6.9 Performance Optimization for Legacy

### 6.9.1 Lazy Loading

```php
class LazyLoader
{
    private array $loadedFiles = [];

    public function loadFile(string $path): void
    {
        if (isset($this->loadedFiles[$path])) {
            return; // Already loaded
        }

        include_once $path;
        $this->loadedFiles[$path] = true;
    }
}
```

### 6.9.2 Caching Hook Results

```php
class HookResultCache
{
    private \Redis $redis;
    private int $ttl = 300; // 5 minutes

    public function get(string $hookKey, array $args): ?string
    {
        $cacheKey = $this->generateKey($hookKey, $args);
        return $this->redis->get($cacheKey);
    }

    public function set(string $hookKey, array $args, string $result): void
    {
        $cacheKey = $this->generateKey($hookKey, $args);
        $this->redis->setex($cacheKey, $this->ttl, $result);
    }

    private function generateKey(string $hookKey, array $args): string
    {
        return 'hook:' . $hookKey . ':' . md5(json_encode($args));
    }
}
```

---

## 6.10 Compatibility Dashboard

### 6.10.1 Hook Ledger View

```typescript
// SolidJS Component
export function HookLedger() {
  const [hooks, setHooks] = createSignal([]);

  onMount(async () => {
    const response = await api.get('/api/legacy/hooks');
    setHooks(response.data);
  });

  return (
    <div class="hook-ledger">
      <h2>Hook Registry</h2>
      <table>
        <thead>
          <tr>
            <th>Tag</th>
            <th>Type</th>
            <th>Priority</th>
            <th>Plugin</th>
            <th>Status</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          <For each={hooks()} fallback={<tr><td colspan="6">No hooks registered</td></tr>}>
            {(hook) => (
              <tr>
                <td>{hook.tag}</td>
                <td>{hook.hook_type}</td>
                <td>{hook.priority}</td>
                <td>{hook.plugin_slug}</td>
                <td>
                  <Switch 
                    checked={hook.is_active} 
                    onChange={(checked) => toggleHook(hook.id, checked)}
                  />
                </td>
                <td>
                  <button onClick={() => viewDetails(hook.id)}>Details</button>
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

### 6.10.2 Compatibility Score

```php
class CompatibilityScorer
{
    public function calculateScore(string $pluginSlug): int
    {
        $score = 100;

        // Deduct for deprecated functions
        if ($this->usesDeprecatedFunctions($pluginSlug)) {
            $score -= 20;
        }

        // Deduct for unsafe SQL
        if ($this->hasUnsafeSQL($pluginSlug)) {
            $score -= 30;
        }

        // Deduct for global variables abuse
        if ($this->abusesGlobals($pluginSlug)) {
            $score -= 10;
        }

        return max(0, $score);
    }
}
```
