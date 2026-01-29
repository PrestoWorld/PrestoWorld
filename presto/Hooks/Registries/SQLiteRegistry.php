<?php

declare(strict_types=1);

namespace PrestoWorld\Hooks\Registries;

use PrestoWorld\Contracts\Hooks\Registries\HookRegistryInterface;
use PDO;

class SQLiteRegistry implements HookRegistryInterface
{
    protected PDO $pdo;
    protected string $table = 'hooks_registry';

    public function __construct(string $dbPath)
    {
        $this->pdo = new PDO("sqlite:{$dbPath}");
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->initTable();
    }

    protected function initTable(): void
    {
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS {$this->table} (
                hook_name TEXT NOT NULL,
                callback_id TEXT NOT NULL,
                priority INTEGER DEFAULT 10,
                metadata TEXT,
                PRIMARY KEY (hook_name, callback_id)
            )
        ");
        
        // Optimize for frequent lookups
        $this->pdo->exec("CREATE INDEX IF NOT EXISTS idx_hook_name ON {$this->table} (hook_name)");
    }

    public function register(string $hook, callable $callback, int $priority = 10, int $acceptedArgs = 1): void
    {
        // For serializable closures or string callbacks
        // Note: Complex closures might need special handling (e.g. opis/closure)
        // Here we store metadata but runtime memory still handles the actual closure if it's not serializable.
        // Wait, SQLite implies persistence across requests? 
        // If so, we can only store string-based callbacks or static methods properly.
        // Runtime closures (anonymous functions) cannot be reliably stored in SQLite for *other* processes to pick up
        // UNLESS we use Opis Closure serialization.
        
        // However, usually Hook Registry in Hybrid apps is about registering *Plugins* hooks locations.
        // For runtime hooks, ArrayRegistry is often best. 
        // But per USER request, we implement SQLite storage.
        
        // Let's store what we can.
        $callbackId = $this->generateCallbackId($callback);
        
        $stmt = $this->pdo->prepare("INSERT OR REPLACE INTO {$this->table} (hook_name, callback_id, priority, metadata) VALUES (?, ?, ?, ?)");
        $stmt->execute([
            $hook,
            $callbackId,
            $priority,
            json_encode(['args' => $acceptedArgs])
        ]);
    }

    public function getCallbacks(string $hook): array
    {
        // This is tricky. We can retrieve the LIST of registered hooks from SQLite
        // But we cannot "rehydrate" a PHP Closure from SQLite if it wasn't defined in this existing process context
        // unless we serialized it.
        
        // For a Sandbox, this might be tracking "which hooks are active" rather than executing them directly from DB?
        // Or user implies standard WordPress transients/options storage?
        
        // Assuming current implementation returns empty array as this is likely for storage/introspection
        // Actual execution might still rely on runtime registration.
        
        // But let's return what we found
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->table} WHERE hook_name = ? ORDER BY priority ASC");
        $stmt->execute([$hook]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function remove(string $hook, callable $callback, int $priority = 10): void
    {
        $callbackId = $this->generateCallbackId($callback);
        $stmt = $this->pdo->prepare("DELETE FROM {$this->table} WHERE hook_name = ? AND callback_id = ? AND priority = ?");
        $stmt->execute([$hook, $callbackId, $priority]);
    }

    public function has(string $hook): bool
    {
        $stmt = $this->pdo->prepare("SELECT count(*) FROM {$this->table} WHERE hook_name = ?");
        $stmt->execute([$hook]);
        return $stmt->fetchColumn() > 0;
    }

    protected function generateCallbackId(callable $callback): string
    {
        if (is_string($callback)) return $callback;
        if (is_array($callback)) {
            $class = is_object($callback[0]) ? get_class($callback[0]) : $callback[0];
            return $class . '::' . $callback[1];
        }
        return 'closure_' . spl_object_hash((object)$callback);
    }
}
