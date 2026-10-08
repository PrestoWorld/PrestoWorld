<?php

declare(strict_types=1);

namespace PrestoWorld\Core\Legacy;

/**
 * LegacyRegistry — hook registry (spec 06 §6.3 + 10 §10.4.11).
 *
 * - Canonical store: in-memory (nhanh cho long-running worker).
 * - Optional SQLite ledger: ghi audit recordHook / plugin entity (dashboard).
 *
 * Public API khớp những gì compile output kỳ vọng:
 * recordHook / removeHook / removeAll / has + registerPlugin & activation.
 */
class LegacyRegistry
{
    public const HOOK_ACTION = 'action';
    public const HOOK_FILTER = 'filter';

/**
     * @var array<string, array<string, list<array{id: string, priority: int, callback: callable, accepted_args: int, type: string}>>>
     */
    private array $hooks = [];

    /**
     * @var array<string, array{id: int, slug: string, path: string, type: string, is_active: bool}>
     */
    private array $entities = [];

    private ?\PDO $ledger = null;

    public function __construct(?string $sqlitePath = null)
    {
        if ($sqlitePath !== null) {
            $this->attachLedger($sqlitePath);
        }
    }

    /**
     * Mở ledger SQLite (optional). Tạo bảng nếu chưa có.
     */
    public function attachLedger(string $path): void
    {
        $this->ledger = new \PDO('sqlite:' . $path);
        $this->ledger->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $this->ledger->exec('PRAGMA journal_mode = WAL');
        $this->ledger->exec(
            'CREATE TABLE IF NOT EXISTS legacy_hooks (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                tag TEXT NOT NULL,
                hook_type TEXT NOT NULL,
                callback_type TEXT NOT NULL,
                callback_data TEXT NOT NULL,
                priority INTEGER NOT NULL,
                accepted_args INTEGER NOT NULL,
                source_file TEXT,
                ioc_meta TEXT,
                created_at TEXT DEFAULT CURRENT_TIMESTAMP
            )',
        );
        $this->ledger->exec(
            'CREATE TABLE IF NOT EXISTS legacy_entities (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                slug TEXT NOT NULL,
                path TEXT,
                type TEXT NOT NULL,
                is_active INTEGER NOT NULL DEFAULT 1
            )',
        );
    }

    /**
     * Ghi hook mới. Callback dạng compiled shim truyền đủ 4 arg; hookType
     * được shim add_filter truyền 'filter' (mode-s giữ nguyên \add_action).
     *
     * @param callable $callback
     */
    public function recordHook(
        string $tag,
        callable $callback,
        int $priority = 10,
        int $acceptedArgs = 1,
        ?string $hookType = null,
    ): string {
        $type = $hookType ?? self::HOOK_ACTION;
        $id = uniqid('hook_', true);

        $this->hooks[$type][$tag] = $this->hooks[$type][$tag] ?? [];
        $this->hooks[$type][$tag][] = [
            'id' => $id,
            'priority' => $priority,
            'callback' => $callback,
            'accepted_args' => $acceptedArgs,
            'type' => $type,
        ];

        $this->persistHook($tag, $type, $callback, $priority, $acceptedArgs);

        return $id;
    }

    /**
     * Gỡ hook khớp tag + (tuỳ chọn) callback + priority.
     *
     * @param callable|null $callback
     */
    public function removeHook(string $tag, ?callable $callback = null, ?int $priority = null, ?string $hookType = null): bool
    {
        $types = $this->typesFor($hookType);
        $removed = false;

        foreach ($types as $type) {
            if (!isset($this->hooks[$type][$tag])) {
                continue;
            }

            $kept = [];
            foreach ($this->hooks[$type][$tag] as $hook) {
                if ($this->matchesFilter($hook, $callback, $priority)) {
                    $removed = true;
                    continue;
                }
                $kept[] = $hook;
            }

            $this->hooks[$type][$tag] = $kept;
            if ($kept === []) {
                unset($this->hooks[$type][$tag]);
            }
        }

        return $removed;
    }

    /**
     * Gỡ toàn bộ hook của một tag (hoặc tất cả hook của tag tại một priority).
     */
    public function removeAll(string $tag, ?int $priority = null, ?string $hookType = null): bool
    {
        if ($priority === null && $hookType === null) {
            $types = [self::HOOK_ACTION, self::HOOK_FILTER];
        } else {
            $types = $this->typesFor($hookType);
        }

        $removed = false;

        foreach ($types as $type) {
            if (!isset($this->hooks[$type][$tag])) {
                continue;
            }

            if ($priority === null) {
                unset($this->hooks[$type][$tag]);
                $removed = true;
                continue;
            }

            $kept = [];
            foreach ($this->hooks[$type][$tag] as $hook) {
                if ($hook['priority'] === $priority) {
                    $removed = true;
                    continue;
                }
                $kept[] = $hook;
            }

            $this->hooks[$type][$tag] = $kept;
            if ($kept === []) {
                unset($this->hooks[$type][$tag]);
            }
        }

        return $removed;
    }

    /**
     * Trả về priority nếu có hook khớp, false nếu không (tương đương has_action).
     *
     * @param callable|null $callback
     * @return int|false
     */
    public function has(string $tag, ?callable $callback = null, ?string $hookType = null): int|false
    {
        foreach ($this->typesFor($hookType) as $type) {
            foreach ($this->hooks[$type][$tag] ?? [] as $hook) {
                if ($this->matchesFilter($hook, $callback, null)) {
                    return $hook['priority'];
                }
            }
        }

        return false;
    }

    /**
     * Hook đã đăng ký của một tag, đã sort theo priority (dùng cho LegacyInvoker).
     *
     * @return list<array{id: string, priority: int, callback: callable, accepted_args: int, type: string}>
     */
    public function hooksFor(string $tag, ?string $hookType = null): array
    {
        $result = [];
        foreach ($this->typesFor($hookType) as $type) {
            foreach ($this->hooks[$type][$tag] ?? [] as $hook) {
                $result[] = $hook;
            }
        }

        usort($result, fn (array $a, array $b): int => $a['priority'] <=> $b['priority']);

        return $result;
    }

    /**
     * @param array{priority?: int, callback?: callable, accepted_args?: int, type?: string} $hook
     * @param callable|null $callback
     */
    private function matchesFilter(array $hook, ?callable $callback, ?int $priority): bool
    {
        if ($priority !== null && $hook['priority'] !== $priority) {
            return false;
        }

        if ($callback !== null && !$this->sameCallback($hook['callback'], $callback)) {
            return false;
        }

        return true;
    }

    private function sameCallback(mixed $left, mixed $right): bool
    {
        if (is_string($left) && is_string($right)) {
            return $left === $right;
        }

        if (is_array($left) && is_array($right)) {
            return count($left) === count($right)
                && array_map('strval', $left) === array_map('strval', $right);
        }

        if ($left instanceof \Closure && $right instanceof \Closure) {
            return $left === $right;
        }

        return false;
    }

    /**
     * @return list<string>
     */
    private function typesFor(?string $hookType): array
    {
        if ($hookType === null) {
            return [self::HOOK_ACTION, self::HOOK_FILTER];
        }

        return [$hookType];
    }

    private function persistHook(string $tag, string $type, callable $callback, int $priority, int $acceptedArgs): void
    {
        if ($this->ledger === null) {
            return;
        }

        $info = $this->callbackInfo($callback);
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        $source = $trace[1]['file'] ?? 'unknown';

        $stmt = $this->ledger->prepare(
            'INSERT INTO legacy_hooks (tag, hook_type, callback_type, callback_data, priority, accepted_args, source_file, ioc_meta)
             VALUES (:tag, :type, :cb_type, :cb_data, :priority, :args, :source, :ioc)',
        );

        $stmt->execute([
            ':tag' => $tag,
            ':type' => $type,
            ':cb_type' => $info['type'],
            ':cb_data' => $info['data'],
            ':priority' => $priority,
            ':args' => $acceptedArgs,
            ':source' => $source,
            ':ioc' => json_encode($this->analyzeIoC($callback)),
        ]);
    }

    /**
     * @return array{type: string, data: string}
     */
    private function callbackInfo(callable $callback): array
    {
        if (is_string($callback)) {
            return ['type' => 'function', 'data' => $callback];
        }

        if (is_array($callback)) {
            return ['type' => 'class_method', 'data' => implode('@', array_map('strval', $callback))];
        }

        if ($callback instanceof \Closure) {
            $ref = new \ReflectionFunction($callback);
            return ['type' => 'closure', 'data' => $ref->getFileName() . ':' . $ref->getStartLine()];
        }

        return ['type' => 'invokable', 'data' => get_class($callback)];
    }

    /**
     * @return array<int, array{type: string, name: string, class?: string}>
     */
    private function analyzeIoC(callable $callback): array
    {
        try {
            $reflection = is_array($callback)
                ? new \ReflectionMethod($callback[0], (string) $callback[1])
                : new \ReflectionFunction($callback);
        } catch (\ReflectionException) {
            return [];
        }

        $meta = [];
        foreach ($reflection->getParameters() as $index => $param) {
            $type = $param->getType();
            if ($type instanceof \ReflectionNamedType && !$type->isBuiltin()) {
                $meta[$index] = ['type' => 'service', 'name' => $param->getName(), 'class' => $type->getName()];
            } else {
                $meta[$index] = ['type' => 'context', 'name' => $param->getName()];
            }
        }

        return $meta;
    }

    /**
     * Đăng ký plugin/theme sandbox (spec 06 §6.3.2).
     */
    public function registerPlugin(string $slug, string $path = '', string $type = 'plugin'): int
    {
        if ($this->ledger !== null) {
            $stmt = $this->ledger->prepare(
                'INSERT INTO legacy_entities (slug, path, type, is_active) VALUES (:slug, :path, :type, 1)',
            );
            $stmt->execute([':slug' => $slug, ':path' => $path, ':type' => $type]);
            $id = (int) $this->ledger->lastInsertId();
        } else {
            $id = count($this->entities) + 1;
        }

        $this->entities[(string) $id] = [
            'id' => $id,
            'slug' => $slug,
            'path' => $path,
            'type' => $type,
            'is_active' => true,
        ];

        return $id;
    }

    public function activatePlugin(int $entityId): void
    {
        $this->setPluginActive($entityId, true);
    }

    public function deactivatePlugin(int $entityId): void
    {
        $this->setPluginActive($entityId, false);
    }

    public function isEntityActive(string $slug): bool
    {
        foreach ($this->entities as $entity) {
            if ($entity['slug'] === $slug) {
                return $entity['is_active'];
            }
        }

        return false;
    }

    /**
     * @return array<int, array{id: int, slug: string, path: string, type: string, is_active: bool}>
     */
    public function entities(): array
    {
        return $this->entities;
    }

    private function setPluginActive(int $entityId, bool $active): void
    {
        foreach ($this->entities as $key => $entity) {
            if ($entity['id'] === $entityId) {
                $this->entities[$key]['is_active'] = $active;

                if ($this->ledger !== null) {
                    $stmt = $this->ledger->prepare('UPDATE legacy_entities SET is_active = :active WHERE id = :id');
                    $stmt->execute([':active' => $active ? 1 : 0, ':id' => $entityId]);
                }

                return;
            }
        }
    }

    public function reset(): void
    {
        $this->hooks = [];
        // Entity registrations derived from source, không cần reset giữa request.
    }
}