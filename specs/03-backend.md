# 03. Backend - Witals Framework & RoadRunner

## 3.1 Witals Framework Core

> Witals là PHP framework tự build, kế thừa kiến trúc của Spiral Framework (Bootloader, DI Container, Event Dispatcher, Pipeline, Middleware) và Cycle ORM. Witals hỗ trợ chạy trên cả **RoadRunner** (stateful worker pool) lẫn **traditional PHP-FPM** (stateless).

### 3.1.1 Bootloader System

**Purpose**: Modular loading of application components

**Standard Bootloaders**:
```php
namespace App\Bootloader;

use Spiral\Boot\Bootloader\Bootloader;

class ConfigBootloader extends Bootloader
{
    public function boot(ConfigurationReader $reader): void
    {
        // Load configuration from .env or wp-config.php
        $this->container->bindSingleton(
            ConfigurationReader::class,
            fn() => new ConfigurationReader(
                basePath: $this->directory->root(),
                mode: $reader->get('APP_MODE', 'auto')
            )
        );
    }
}
```

**Legacy Bridge Bootloader**:
```php
class LegacyBridgeBootloader extends Bootloader
{
    public function boot(LegacyRegistry $registry): void
    {
        // Initialize SQLite registry for wp.org plugins
        $registry->initialize();
        
        // NOTE: wp-compatibility.php (shim functions) KHÔNG được load ở đây.
        // File shim chỉ được LegacyInvoker load on-demand khi có legacy plugin
        // request thực sự cần đến. Điều này giữ boot time tối thiểu.
        // Xem: PrestoWorld\Core\Legacy\LegacyInvoker::loadShimLayer()
    }
}
```

### 3.1.2 Dependency Injection Container

**IoC Pattern**:
```php
class MyService
{
    public function __construct(
        private DatabaseInterface $db,
        private CacheInterface $cache,
        private LegacyRegistry $registry
    ) {}
}
```

**Automatic Resolution**:
- Type-hint based injection
- Singleton configuration
- Scoped contexts (per-request)
- Lazy initialization

### 3.1.3 Event System (Observer Pattern)

**Replaces add_action/do_action**:
```php
// Define Event
class PostSaved
{
    public function __construct(
        public readonly int $postId,
        public readonly array $post
    ) {}
}

// Dispatch Event
$eventDispatcher->dispatch(new PostSaved($postId, $post));

// Listen to Event
#[Listener]
class PostListener
{
    public function onPostSaved(PostSaved $event): void
    {
        // Handle post saved logic
    }
}
```

### 3.1.4 Pipeline Pattern (Filter Replacement)

**Replaces add_filter/apply_filters**:
```php
$content = $pipeline
    ->send($rawContent)
    ->through([
        SlugifyFilter::class,
        XSSFilter::class,
        HtmlMinifier::class,
    ])
    ->then(fn ($content) => $content);
```

---

## 3.2 RoadRunner Configuration

### 3.2.1 Basic Setup (.rr.yaml)

```yaml
version: "3"

rpc:
  listen: tcp://127.0.0.1:6001

server:
  command: "php app.php"
  relay: pipes
  env:
    - APP_ENV=production

http:
  address: :8080
  middleware: ["static", "gzip"]
  static:
    dir: "public"
    forbid: [".php", ".env"]
  pool:
    num_workers: 4
    max_jobs: 64
    supervisor:
      max_worker_memory: 128
      ttl: 60s

logs:
  level: info
  mode: production
```

### 3.2.2 Worker Pool Optimization

**For High-Traffic SaaS**:
```yaml
pool:
  num_workers: 16
  max_jobs: 1000
  supervisor:
    max_worker_memory: 256
    ttl: 120s
```

**For Shared Hosting (Low Resources)**:
```yaml
pool:
  num_workers: 2
  max_jobs: 32
  supervisor:
    max_worker_memory: 64
    ttl: 30s
```

### 3.2.3 Persistent Connections

**Database Connection Pooling**:
```yaml
# RoadRunner doesn't need explicit config
# Spiral handles it via Cycle ORM
services:
  db.pool.max: 10
  db.pool.idle: 2
```

**Cache Connection**:
```yaml
# Redis or Memcached for shared cache
cache:
  driver: redis
  servers:
    - "tcp://127.0.0.1:6379"
```

---

## 3.3 Configuration System

### 3.3.1 ConfigurationReader

**Purpose**: Single source of truth for configuration

**Implementation**:
```php
namespace App\Infrastructure\Config;

class ConfigurationReader
{
    private array $cache = [];
    private array $transformers = [];

    public function __construct(
        private string $basePath,
        private string $mode = 'auto' // 'env', 'saas', 'legacy'
    ) {
        $this->registerDefaultTransformers();
    }

    public function get(string $key, mixed $default = null): mixed
    {
        if (isset($this->cache[$key])) {
            return $this->cache[$key];
        }

        $value = $this->lookup($key);

        if ($value === null) {
            return $default;
        }

        if (isset($this->transformers[$key])) {
            $value = $this->transformers[$key]($value);
        }

        return $this->cache[$key] = $value;
    }

    private function lookup(string $key): mixed
    {
        // Priority 1: System ENV (SaaS/RoadRunner)
        if ($this->mode === 'saas' || ($val = getenv($key)) !== false) {
            return $val;
        }

        // Priority 2: Legacy wp-config.php
        if ($this->isLegacyMode() && defined($key)) {
            return constant($key);
        }

        // Priority 3: .env file
        return $_ENV[$key] ?? $_SERVER[$key] ?? null;
    }

    private function isLegacyMode(): bool
    {
        $configPath = $this->basePath . '/wp-config.php';
        if (file_exists($configPath)) {
            require_once $configPath;
            return true;
        }
        return false;
    }

    public function addTransformer(string $key, callable $transformer): void
    {
        $this->transformers[$key] = $transformer;
    }

    private function registerDefaultTransformers(): void
    {
        $this->addTransformer('DB_PORT', fn($v) => (int)$v);
        $this->addTransformer('WP_DEBUG', function($v) {
            return in_array(strtolower((string)$v), ['true', '1', 'on', 'yes']);
        });
    }
}
```

### 3.3.2 Usage Example

```php
// In Controller
public function index(ConfigurationReader $reader): string
{
    $dbName = $reader->get('DB_NAME', 'prestoworld_db');
    $debug = $reader->get('WP_DEBUG', false);
    
    // Custom transformer
    $reader->addTransformer('MEMORY_LIMIT', fn($v) => $v . 'MB');
    
    return $this->view->render('index', compact('dbName', 'debug'));
}
```

---

## 3.4 Database Layer

### 3.4.1 Cycle ORM Configuration

**PostgreSQL (Main Database)**:
```php
// config/database.php
return [
    'default' => 'postgres',
    'databases' => [
        'postgres' => [
            'driver' => 'postgres',
            'connection' => 'pgsql:host=localhost;dbname=prestoworld',
            'username' => env('DB_USER'),
            'password' => env('DB_PASS'),
        ],
    ],
];
```

**SQLite (Legacy Sandbox)**:
```php
'sqlite' => [
    'driver' => 'sqlite',
    'connection' => 'sqlite:' . directory('storage') . '/presto_legacy.sqlite',
],
```

### 3.4.2 Schema Definition

**Core Tables (PostgreSQL)**:
```php
namespace App\Database\Schema;

use Cycle\Database\Schema as Schema;

class PostSchema
{
    public function __invoke(Schema $schema): void
    {
        $table = $schema->table('posts')->declare();
        
        $table->columns()
            ->primary('id')
            ->string('title')
            ->text('content')
            ->jsonb('meta')
            ->timestamp('created_at')
            ->timestamp('updated_at');
        
        $table->index('title');
        $table->index('created_at');
    }
}
```

---

## 3.5 Middleware System

### 3.5.1 Custom Middleware

**Error Masking Middleware**:
```php
namespace App\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class ErrorMaskingMiddleware implements MiddlewareInterface
{
    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        try {
            return $handler->handle($request);
        } catch (\Exception $e) {
            // Log error
            $this->logger->error($e->getMessage());
            
            // Mask sensitive data
            $masked = $this->dataMasker->mask($e->getMessage());
            
            // Send report to Cloudflare
            $this->reporter->send($masked);
            
            // Return user-friendly error
            return $this->response->html(
                $this->view->render('errors/masked'),
                500
            );
        }
    }
}
```

### 3.5.2 Middleware Registration

```php
// In Bootloader
public function boot(HttpMiddlewareRegistry $middleware): void
{
    $middleware->add(ErrorMaskingMiddleware::class);
    $middleware->add(QueryLoggingMiddleware::class);
    $middleware->add(CsrfMiddleware::class);
}
```

---

## 3.6 Queue System

### 3.6.1 Job Definition

```php
namespace App\Job;

use Spiral\Queue\JobHandler;

class SendErrorReportJob extends JobHandler
{
    public function handle(string $payload): void
    {
        $data = json_decode($payload, true);
        
        // Send to Cloudflare Worker
        $this->httpClient->post(
            'https://api.prestoworld.io/error-report',
            $data
        );
    }
}
```

### 3.6.2 Queue Configuration

```yaml
# RoadRunner configuration
kafka:
  addr: [localhost:9092]
  topic: presto-queue
  consumer_group: presto-workers
```

---

## 3.7 API Endpoints

### 3.7.1 REST API Structure

```php
namespace App\Controller\Api;

use Spiral\Router\Annotation\Route;

class PluginController
{
    #[Route(route: '/api/plugins', methods: 'GET')]
    public function list(PluginRepository $plugins): array
    {
        return $plugins->findAll();
    }

    #[Route(route: '/api/plugins/<id>', methods: 'GET')]
    public function show(string $id, PluginRepository $plugins): array
    {
        return $plugins->findByPK($id);
    }

    #[Route(route: '/api/plugins', methods: 'POST')]
    public function install(Request $request, PluginService $service): array
    {
        $slug = $request->input('slug');
        return $service->install($slug);
    }
}
```

### 3.7.2 RPC API (RoadRunner)

```php
namespace App\Rpc;

use Spiral\Goridge\RPC\RPCInterface;

class PluginRPC
{
    public function activate(string $pluginId, RPCInterface $rpc): bool
    {
        // Activates plugin and returns status
        return $this->pluginService->activate($pluginId);
    }
}
```

---

## 3.8 Performance Monitoring

### 3.8.1 Metrics Collection

```php
namespace App\Metrics;

class PerformanceMonitor
{
    public function recordQuery(string $query, float $time): void
    {
        if ($time > 0.1) { // Slow query (>100ms)
            $this->logger->warning("Slow query: {$query} ({$time}s)");
            $this->metrics->increment('slow_queries');
        }
    }

    public function recordHookExecution(string $hook, float $time): void
    {
        $this->metrics->histogram('hook_execution_time', $time, ['hook' => $hook]);
    }
}
```

### 3.8.2 Health Check Endpoint

```php
#[Route(route: '/health', methods: 'GET')]
public function health(): array
{
    return [
        'status' => 'healthy',
        'database' => $this->db->isConnected() ? 'up' : 'down',
        'cache' => $this->cache->isConnected() ? 'up' : 'down',
        'workers' => $this->roadRunner->getWorkerCount(),
    ];
}
```

---

## 3.9 Development Workflow

### 3.9.1 Local Development

```bash
# Start RoadRunner
rr serve

# Watch for changes
rr serve -w

# Test with pest
./vendor/bin/pest

# Check code style
./vendor/bin/phpcs
```

### 3.9.2 Debugging

```php
// Enable debug mode
$app_env = 'development';

// RoadRunner debug logs
logs:
  level: debug
  output: stdout
```

---

## 3.10 PHP Coding Convention: PSR-4 Static Methods

> **Design Decision** (cross-ref [02 §2.8](./02-architecture.md)): Mọi logic trong
> PrestoWorld Core **phải** là PSR-4 class static method, không được là PHP user function.

### Ví dụ chuẩn

```php
// ✅ Chuẩn — dùng trong mọi Controller, Service, Bootloader của Core
use PrestoWorld\Core\Escape;
use PrestoWorld\Core\Format;
use PrestoWorld\Core\Security\Nonce;

$clean  = Escape::html($userInput);
$date   = Format::date($timestamp, 'd/m/Y');
$token  = Nonce::create('save_post');
```

```php
// ❌ Không bao giờ viết thế này trong Core
function presto_escape_html(string $s): string { ... }
```

### Tổ chức namespace

```
PrestoWorld\Core\Escape          → html(), attr(), url(), js(), xml()
PrestoWorld\Core\Sanitize        → text(), email(), key(), fileName() ...
PrestoWorld\Core\Format          → date(), number(), size(), trimWords() ...
PrestoWorld\Core\Security\Nonce  → create(), verify(), field()
PrestoWorld\Core\Security\Auth   → hash(), verify()
PrestoWorld\Core\Url             → home(), site(), permalink() ...
PrestoWorld\Core\Path            → home(), plugin() ...
```

> Autoload theo PSR-4 qua Composer: chỉ nạp class khi thực sự được gọi.
> Không có file nào chứa hàng loạt function definitions bị load tại boot.


### 3.10.1 Docker Configuration

```dockerfile
FROM spiralc/php:8.3

WORKDIR /app

COPY . /app

RUN composer install --no-dev --optimize-autoloader

RUN chmod +x rr

EXPOSE 8080

CMD ["rr", "serve"]
```

### 3.10.2 Kubernetes Deployment

```yaml
apiVersion: apps/v1
kind: Deployment
metadata:
  name: prestoworld
spec:
  replicas: 3
  selector:
    matchLabels:
      app: prestoworld
  template:
    metadata:
      labels:
        app: prestoworld
    spec:
      containers:
      - name: prestoworld
        image: prestoworld:latest
        ports:
        - containerPort: 8080
        resources:
          requests:
            memory: "256Mi"
            cpu: "250m"
          limits:
            memory: "512Mi"
            cpu: "500m"
```
