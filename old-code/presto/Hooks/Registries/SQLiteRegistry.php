<?php

declare(strict_types=1);

namespace PrestoWorld\Hooks\Registries;

use PrestoWorld\Contracts\Hooks\Registries\HookRegistryInterface;
use PrestoWorld\Contracts\Hooks\HookStateType;
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
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                type TEXT NOT NULL,
                hook TEXT NOT NULL,
                callback TEXT NOT NULL,
                priority INTEGER NOT NULL,
                state_type TEXT NOT NULL
            )
        ");
        
        $this->pdo->exec("CREATE INDEX IF NOT EXISTS idx_hook_lookup ON {$this->table} (type, hook)");
    }

    public function set(string $type, string $hook, string $callback, int $priority, HookStateType $stateType = HookStateType::VOLATILE): void
    {
        // Try to avoid duplicates
        $stmt = $this->pdo->prepare("SELECT id FROM {$this->table} WHERE type = ? AND hook = ? AND callback = ? AND priority = ?");
        $stmt->execute([$type, $hook, $callback, $priority]);
        if ($stmt->fetch()) {
            return;
        }

        $stmt = $this->pdo->prepare("INSERT INTO {$this->table} (type, hook, callback, priority, state_type) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$type, $hook, $callback, $priority, $stateType->value]);
    }

    public function get(string $type, string $hook): array
    {
        $stmt = $this->pdo->prepare("SELECT callback, priority, state_type FROM {$this->table} WHERE type = ? AND hook = ? ORDER BY priority ASC");
        $stmt->execute([$type, $hook]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function remove(string $type, string $hook, string $callback, int $priority): void
    {
        $stmt = $this->pdo->prepare("DELETE FROM {$this->table} WHERE type = ? AND hook = ? AND callback = ? AND priority = ?");
        $stmt->execute([$type, $hook, $callback, $priority]);
    }

    public function clear(string $type, string $hook, ?int $priority = null): void
    {
        if ($priority === null) {
            $stmt = $this->pdo->prepare("DELETE FROM {$this->table} WHERE type = ? AND hook = ?");
            $stmt->execute([$type, $hook]);
        } else {
            $stmt = $this->pdo->prepare("DELETE FROM {$this->table} WHERE type = ? AND hook = ? AND priority = ?");
            $stmt->execute([$type, $hook, $priority]);
        }
    }

    public function has(string $type, string $hook): bool
    {
        $stmt = $this->pdo->prepare("SELECT count(*) FROM {$this->table} WHERE type = ? AND hook = ?");
        $stmt->execute([$type, $hook]);
        return $stmt->fetchColumn() > 0;
    }
}
