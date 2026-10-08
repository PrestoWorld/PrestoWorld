# 05. Database - PostgreSQL & SQLite Strategy

## 5.1 Dual Database Architecture

### 5.1.1 Architecture Overview

```
┌─────────────────────────────────────────────────────────┐
│                  PrestoWorld Database Layer             │
├─────────────────────────────────────────────────────────┤
│                                                          │
│  ┌──────────────────────┐  ┌──────────────────────┐    │
│  │   PostgreSQL         │  │      SQLite          │    │
│  │   (Core Data)        │  │   (Legacy Sandbox)   │    │
│  │                      │  │                      │    │
│  │  - Users             │  │  - Plugin Registry   │    │
│  │  - Posts             │  │  - Hook Ledger       │    │
│  │  - Permissions       │  │  - Legacy Tables     │    │
│  │  - Settings          │  │  - Plugin Data       │    │
│  │  - Native Plugins    │  │                      │    │
│  └──────────┬───────────┘  └──────────┬───────────┘    │
│             │                         │                   │
│             └──────────┬──────────────┘                   │
│                        │                                 │
│            ┌───────────▼────────────┐                  │
│            │   $wpdb Transformer   │                  │
│            │   (Router & Masker)   │                  │
│            └───────────┬────────────┘                  │
│                        │                                 │
│            ┌───────────▼────────────┐                  │
│            │  Application Layer     │                  │
│            └───────────────────────┘                  │
└─────────────────────────────────────────────────────────┘
```

### 5.1.2 Routing Logic

**PrestoWpdb Router**:
```php
namespace PrestoWorld\Core\Database;

class PrestoWpdb
{
    private $postgreConnection;
    private $sqliteConnection;
    private $transformer;

    public function __construct(
        \PDO $postgre,
        \PDO $sqlite,
        QueryTransformer $transformer
    ) {
        $this->postgreConnection = $postgre;
        $this->sqliteConnection = $sqlite;
        $this->transformer = $transformer;
    }

    public function query(string $query)
    {
        if ($this->isCoreTable($query)) {
            return $this->executePostgres($query);
        } else {
            return $this->executeSqlite($query);
        }
    }

    private function isCoreTable(string $query): bool
    {
        $coreTables = ['posts', 'users', 'options', 'metadata', 'comments'];
        foreach ($coreTables as $table) {
            if (str_contains($query, $this->prefix . $table)) {
                return true;
            }
        }
        return false;
    }

    private function executePostgres(string $query)
    {
        $transformed = $this->transformer->toPostgres($query);
        return $this->postgreConnection->query($transformed);
    }

    private function executeSqlite(string $query)
    {
        $transformed = $this->transformer->toSqlite($query);
        return $this->sqliteConnection->query($transformed);
    }
}
```

---

## 5.2 PostgreSQL (Main Database)

### 5.2.1 Schema Design

**Users Table**:
```sql
CREATE TABLE pw_users (
    id SERIAL PRIMARY KEY,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    display_name VARCHAR(100),
    role VARCHAR(50) DEFAULT 'subscriber',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_users_email ON pw_users(email);
CREATE INDEX idx_users_role ON pw_users(role);
```

**Posts Table**:
```sql
CREATE TABLE pw_posts (
    id SERIAL PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    content TEXT,
    status VARCHAR(50) DEFAULT 'draft',
    post_type VARCHAR(50) DEFAULT 'post',
    author_id INTEGER REFERENCES pw_users(id),
    meta JSONB DEFAULT '{}',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    published_at TIMESTAMP
);

CREATE INDEX idx_posts_slug ON pw_posts(slug);
CREATE INDEX idx_posts_status ON pw_posts(status);
CREATE INDEX idx_posts_type ON pw_posts(post_type);
CREATE INDEX idx_posts_author ON pw_posts(author_id);

-- GIN index for JSONB queries
CREATE INDEX idx_posts_meta ON pw_posts USING GIN (meta);
```

**Options Table**:
```sql
CREATE TABLE pw_options (
    option_name VARCHAR(191) PRIMARY KEY,
    option_value TEXT,
    autoload BOOLEAN DEFAULT false,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

### 5.2.2 JSONB Usage

**Meta Data**:
```sql
-- Insert post with meta
INSERT INTO pw_posts (title, slug, meta) 
VALUES ('Test Post', 'test-post', '{"author": "John", "tags": ["tech", "web"]}'::jsonb);

-- Query meta
SELECT * FROM pw_posts 
WHERE meta->>'author' = 'John';

-- Query array in meta
SELECT * FROM pw_posts 
WHERE meta->'tags' @> '"tech"';
```

### 5.2.3 Connection Configuration

```php
// config/database.php
return [
    'default' => 'postgres',
    'databases' => [
        'postgres' => [
            'driver' => Cycle\Database\Driver\Postgres\Driver::class,
            'connection' => 'pgsql:host=localhost;port=5432;dbname=prestoworld',
            'username' => env('DB_USER'),
            'password' => env('DB_PASSWORD'),
            'options' => [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ],
        ],
    ],
];
```

---

## 5.3 SQLite (Legacy Sandbox)

### 5.3.1 Schema Design

**Legacy Entities**:
```sql
CREATE TABLE legacy_entities (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    slug TEXT UNIQUE,
    type TEXT CHECK(type IN ('plugin', 'theme')),
    path TEXT,
    is_active INTEGER DEFAULT 1,
    version TEXT,
    installed_at DATETIME DEFAULT CURRENT_TIMESTAMP
);
```

**Legacy Hooks Registry**:
```sql
CREATE TABLE legacy_hooks (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    entity_id INTEGER,
    hook_type TEXT CHECK(hook_type IN ('action', 'filter')),
    tag TEXT,
    callback_type TEXT, -- 'function', 'class_method', 'closure'
    callback_data TEXT,
    priority INTEGER DEFAULT 10,
    accepted_args INTEGER DEFAULT 1,
    source_file TEXT,
    ioc_meta TEXT, -- JSON for IoC injection
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (entity_id) REFERENCES legacy_entities(id) ON DELETE CASCADE
);

CREATE INDEX idx_hook_lookup ON legacy_hooks (tag, priority, hook_type);
CREATE INDEX idx_entity_hooks ON legacy_hooks (entity_id);
```

**Legacy Dependencies**:
```sql
CREATE TABLE legacy_dependencies (
    plugin_id INTEGER,
    requires_plugin_slug TEXT,
    version_constraint TEXT,
    FOREIGN KEY (plugin_id) REFERENCES legacy_entities(id)
);
```

### 5.3.2 SQLite Optimization

**PRAGMA Settings**:
```php
// On connection
$db = new PDO("sqlite:" . $path);
$db->exec("PRAGMA journal_mode = WAL;");       // Write-Ahead Logging
$db->exec("PRAGMA synchronous = NORMAL;");      // Balanced speed/safety
$db->exec("PRAGMA temp_store = MEMORY;");       // Temp tables in RAM
$db->exec("PRAGMA cache_size = -64000;");       // 64MB cache
$db->exec("PRAGMA page_size = 4096;");          // 4KB pages
```

**WITHOUT ROWID for Small Tables**:
```sql
-- For frequently accessed small tables
CREATE TABLE legacy_config (
    key TEXT PRIMARY KEY,
    value TEXT
) WITHOUT ROWID;
```

### 5.3.3 Connection Pooling

```php
class SQLiteConnectionPool
{
    private static $instance;
    private $connection;

    public static function getInstance(string $path): PDO
    {
        if (!self::$instance) {
            self::$instance = new PDO("sqlite:$path");
            self::$instance->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Apply optimizations
            self::$instance->exec("PRAGMA journal_mode = WAL;");
            self::$instance->exec("PRAGMA synchronous = NORMAL;");
        }
        
        return self::$instance;
    }
}
```

---

## 5.4 Query Transformer

> **Runtime side.** Bản rule set đầy đủ (DDL/DML/function rules, table mapping `wp_* → pw_*`,
> `$wpdb` API → Cycle ORM) là canonical tại [10.3 WordPress Compiler](./10-wordpress-compiler.md).
> Section này chỉ giữ các rule chính chạy khi plugin thực thi.

### 5.4.1 MySQL → PostgreSQL

**Transformation Rules**:
```php
namespace PrestoWorld\Core\Database;

class QueryTransformer
{
    private array $postgresRules = [
        '/`([^`]+)`/' => '"$1"',                    // Backticks to double quotes
        '/TINYINT\(1\)/i' => 'BOOLEAN',             // TINYINT(1) to BOOLEAN
        '/INT\((\d+)\)/i' => 'INTEGER',              // INT(N) to INTEGER
        '/AUTO_INCREMENT/i' => 'SERIAL',             // AUTO_INCREMENT to SERIAL
        '/ENGINE=\w+/i' => '',                        // Remove ENGINE clause
        '/DEFAULT CURRENT_TIMESTAMP/i' => 'DEFAULT CURRENT_TIMESTAMP',
        '/NOW\(\)/i' => 'CURRENT_TIMESTAMP',          // NOW() to CURRENT_TIMESTAMP
        '/UNSIGNED/i' => '',                          // Remove UNSIGNED
        '/BINARY/i' => '',                            // Remove BINARY
    ];

    public function toPostgres(string $sql): string
    {
        $sql = preg_replace(array_keys($this->postgresRules), array_values($this->postgresRules), $sql);
        
        // Handle LIMIT/OFFSET
        $sql = $this->transformLimitOffset($sql);
        
        return $sql;
    }

    private function transformLimitOffset(string $sql): string
    {
        // MySQL: LIMIT 10 OFFSET 5
        // PostgreSQL: LIMIT 10 OFFSET 5 (same syntax)
        // No transformation needed
        return $sql;
    }
}
```

### 5.4.2 MySQL → SQLite

**Transformation Rules**:
```php
private array $sqliteRules = [
    '/`([^`]+)`/' => '"$1"',                      // Backticks to double quotes
    '/AUTO_INCREMENT/i' => 'AUTOINCREMENT',       // AUTO_INCREMENT to AUTOINCREMENT
    '/ENGINE=\w+/i' => '',                         // Remove ENGINE
    '/UNSIGNED/i' => '',                           // Remove UNSIGNED
    '/ENUM\([^)]+\)/i' => 'TEXT',                 // ENUM to TEXT
    '/SET.*NULL/i' => '',                          // Remove SET NULL
];

public function toSqlite(string $sql): string
{
    $sql = preg_replace(array_keys($this->sqliteRules), array_values($this->sqliteRules), $sql);
    
    // Remove foreign key constraints (SQLite limited support)
    $sql = preg_replace('/FOREIGN KEY.*?,/i', '', $sql);
    
    return $sql;
}
```

### 5.4.3 Advanced Transformations

**Auto-Correction**:
```php
public function autoCorrect(string $sql): string
{
    // Fix STRAIGHT_JOIN (MySQL) to INNER JOIN
    $sql = preg_replace('/STRAIGHT_JOIN/i', 'INNER JOIN', $sql);
    
    // Fix GROUP BY (MySQL loose) to strict PostgreSQL
    // This requires parsing the query
    
    return $sql;
}
```

---

## 5.5 Data Masker

### 5.5.1 Pattern Definitions

```php
namespace PrestoWorld\Core\Database;

class DataMasker
{
    private array $patterns = [
        'email' => '/[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,4}/i',
        'phone' => '/(\+?\d{1,3}[- ]?)?\d{10}/',
        'password' => '/(password|passwd|pwd|secret)\s*=\s*[\'"][^\'"]+[\'"]/i',
        'credit_card' => '/\b(?:\d[ -]*?){13,16}\b/',
        'api_key' => '/(api[_-]?key|token)\s*=\s*[\'"][^\'"]{20,}[\'"]/i',
        'ssn' => '/\b\d{3}[-.]?\d{2}[-.]?\d{4}\b/',
    ];

    public function mask(string $query): string
    {
        foreach ($this->patterns as $label => $pattern) {
            $query = preg_replace($pattern, "[MASKED_" . strtoupper($label) . "]", $query);
        }
        return $query;
    }
}
```

### 5.5.2 Usage in Error Reporting

```php
try {
    $wpdb->query($query);
} catch (\PDOException $e) {
    $maskedQuery = $dataMasker->mask($query);
    
    $this->errorReporter->send([
        'original_query' => $maskedQuery,
        'error_message' => $e->getMessage(),
        'plugin' => $this->getCurrentPlugin(),
    ]);
}
```

---

## 5.6 Migration System

### 5.6.1 Migration Format

```php
namespace App\Database\Migration;

use Cycle\Database\Schema as Schema;

class CreatePostsTable
{
    public function up(Schema $schema): void
    {
        $table = $schema->table('posts')->declare();
        
        $table->columns()
            ->primary('id')
            ->string('title')
            ->string('slug')
            ->text('content')
            ->string('status')
            ->jsonb('meta')
            ->timestamp('created_at');
        
        $table->index('slug');
        $table->index('status');
    }

    public function down(Schema $schema): void
    {
        $schema->table('posts')->drop();
    }
}
```

### 5.6.2 Running Migrations

```bash
# Run all pending migrations
php app.php migrate

# Rollback last migration
php app.php migrate:rollback

# Create new migration
php app.php migrate:create CreateUsersTable
```

---

## 5.7 Backup & Restore

### 5.7.1 PostgreSQL Backup

```bash
# Backup
pg_dump -U postgres prestoworld > backup_$(date +%Y%m%d).sql

# Restore
psql -U postgres prestoworld < backup_20240101.sql
```

### 5.7.2 SQLite Backup

```php
class SQLiteBackup
{
    public function backup(string $source, string $destination): void
    {
        copy($source, $destination);
        
        // Compress
        $compressed = gzencode(file_get_contents($destination), 9);
        file_put_contents($destination . '.gz', $compressed);
    }

    public function restore(string $backup, string $destination): void
    {
        if (str_ends_with($backup, '.gz')) {
            $content = gzdecode(file_get_contents($backup));
        } else {
            $content = file_get_contents($backup);
        }
        
        file_put_contents($destination, $content);
    }
}
```

### 5.7.3 Cloudflare R2 Backup

```php
class R2Backup
{
    public function uploadToR2(string $localFile, string $remoteKey): void
    {
        $s3 = new \Aws\S3\S3Client([
            'region' => 'auto',
            'endpoint' => env('R2_ENDPOINT'),
            'credentials' => [
                'key' => env('R2_ACCESS_KEY'),
                'secret' => env('R2_SECRET_KEY'),
            ],
        ]);

        $s3->putObject([
            'Bucket' => env('R2_BUCKET'),
            'Key' => $remoteKey,
            'SourceFile' => $localFile,
        ]);
    }
}
```

---

## 5.8 Performance Monitoring

### 5.8.1 Slow Query Detection

```php
class QueryMonitor
{
    private float $slowQueryThreshold = 0.1; // 100ms

    public function logQuery(string $query, float $time): void
    {
        if ($time > $this->slowQueryThreshold) {
            $this->logger->warning("Slow query detected", [
                'query' => substr($query, 0, 500),
                'time' => $time,
            ]);
            
            $this->metrics->histogram('query_duration', $time, [
                'type' => $this->getDatabaseType($query),
            ]);
        }
    }
}
```

### 5.8.2 Connection Pool Monitoring

```php
class ConnectionPoolMonitor
{
    public function getStats(): array
    {
        return [
            'postgres' => [
                'active' => $this->postgresPool->getActiveCount(),
                'idle' => $this->postgresPool->getIdleCount(),
                'max' => $this->postgresPool->getMaxCount(),
            ],
            'sqlite' => [
                'status' => 'connected',
                'size' => filesize($this->sqlitePath),
                'wal_size' => filesize($this->sqlitePath . '-wal'),
            ],
        ];
    }
}
```

---

## 5.9 Database Health Checks

### 5.9.1 PostgreSQL Health

```php
public function checkPostgresHealth(): array
{
    try {
        $result = $this->postgres->query("SELECT version()");
        $version = $result->fetchColumn();
        
        $connections = $this->postgres->query("
            SELECT count(*) FROM pg_stat_activity 
            WHERE datname = current_database()
        ")->fetchColumn();
        
        return [
            'status' => 'healthy',
            'version' => $version,
            'connections' => $connections,
        ];
    } catch (\Exception $e) {
        return [
            'status' => 'unhealthy',
            'error' => $e->getMessage(),
        ];
    }
}
```

### 5.9.2 SQLite Health

```php
public function checkSqliteHealth(): array
{
    $size = filesize($this->sqlitePath);
    $walSize = filesize($this->sqlitePath . '-wal');
    
    // Check integrity
    $result = $this->sqlite->query("PRAGMA integrity_check");
    $integrity = $result->fetchColumn();
    
    return [
        'status' => $integrity === 'ok' ? 'healthy' : 'corrupted',
        'size' => $this->formatBytes($size),
        'wal_size' => $this->formatBytes($walSize),
        'integrity' => $integrity,
    ];
}
```
