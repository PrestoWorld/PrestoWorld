<?php

declare(strict_types=1);

namespace PrestoWorld\Hooks\Registries;

use PrestoWorld\Contracts\Hooks\Registries\HookRegistryInterface;
use PrestoWorld\Contracts\Hooks\HookStateType;
use Redis;

class RedisRegistry implements HookRegistryInterface
{
    protected Redis $redis;
    protected string $prefix = 'presto_hooks:';

    public function __construct(Redis $redis)
    {
        $this->redis = $redis;
    }

    public function set(string $type, string $hook, string $callback, int $priority, HookStateType $stateType = HookStateType::VOLATILE): void
    {
        $key = $this->prefix . $type . ':' . $hook;
        $payload = json_encode([
            'callback' => $callback,
            'priority' => $priority,
            'state_type' => $stateType->value
        ]);
        
        $this->redis->zAdd($key, $priority, $payload);
    }

    public function get(string $type, string $hook): array
    {
        $key = $this->prefix . $type . ':' . $hook;
        $items = $this->redis->zRange($key, 0, -1);
        
        $results = [];
        foreach ($items as $item) {
            $results[] = json_decode($item, true);
        }
        
        return $results;
    }

    public function remove(string $type, string $hook, string $callback, int $priority): void
    {
        $key = $this->prefix . $type . ':' . $hook;
        $items = $this->redis->zRange($key, 0, -1);
        
        foreach ($items as $item) {
            $data = json_decode($item, true);
            if ($data['callback'] === $callback && $data['priority'] === $priority) {
                $this->redis->zRem($key, $item);
            }
        }
    }

    public function clear(string $type, string $hook, ?int $priority = null): void
    {
        $key = $this->prefix . $type . ':' . $hook;
        
        if ($priority === null) {
            $this->redis->del($key);
            return;
        }

        $items = $this->redis->zRange($key, 0, -1);
        foreach ($items as $item) {
            $data = json_decode($item, true);
            if ($data['priority'] === $priority) {
                $this->redis->zRem($key, $item);
            }
        }
    }

    public function has(string $type, string $hook): bool
    {
        $key = $this->prefix . $type . ':' . $hook;
        return (bool)$this->redis->exists($key);
    }
}
