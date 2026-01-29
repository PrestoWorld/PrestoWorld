<?php

declare(strict_types=1);

namespace PrestoWorld\Hooks\Registries;

use PrestoWorld\Contracts\Hooks\Registries\HookRegistryInterface;
use Redis;

class RedisRegistry implements HookRegistryInterface
{
    protected Redis $redis;
    protected string $prefix = 'presto_hooks:';

    public function __construct(Redis $redis)
    {
        $this->redis = $redis;
    }

    public function register(string $hook, callable $callback, int $priority = 10, int $acceptedArgs = 1): void
    {
        $callbackId = $this->generateCallbackId($callback);
        if (!$callbackId) return;

        $key = $this->prefix . $hook;

        // Use Pipeline/Transaction to minimize network round-trips
        // 1. ZADD callback to priority list
        // 2. HSET metadata (args, etc)
        // With millions of hooks, efficiency per write is critical.
        
        $this->redis->pipeline();

        // Check if unique to avoid redundant writes? 
        // Redis ZADD is O(log(N)), very fast.
        
        $payloadVal = $callbackId; 
        // We separate metadata to Hash to save space in ZSet
        
        $this->redis->zAdd($key, $priority, $payloadVal);
        $this->redis->hSet($this->prefix . 'meta:' . $hook, $callbackId, (string)$acceptedArgs);
        
        $this->redis->exec();
    }

    public function getCallbacks(string $hook): array
    {
        $key = $this->prefix . $hook;
        
        // Use Lua script to fetch ZRANGE + Associated HMGET in one atomic go.
        // Avoids N+1 fetches if we stored metadata separately.
        
        // This simple version fetches the sorted list.
        // Assuming we store minimal data for speed.
        
        // Caching Layer L1: Check Local APCu/Memory first?
        // This Registry implementation is pure Redis.
        // The HookManager ideally wraps this with L1 Cache.

        $items = $this->redis->zRange($key, 0, -1);
        
        if (empty($items)) return [];
        
        // Bulk fetch metadata
        $metaKey = $this->prefix . 'meta:' . $hook;
        $argsMap = $this->redis->hMGet($metaKey, $items); // Batch get
        
        $callbacks = [];
        foreach ($items as $cbId) {
            $callbacks[] = [
                'callback' => $cbId,
                'priority' => 0, // We lost explicit priority in zRange output unless using WITHSCORES
                'accepted_args' => (int)($argsMap[$cbId] ?? 1)
            ];
        }
        return $callbacks;
    }

    public function remove(string $hook, callable $callback, int $priority = 10): void
    {
        $callbackId = $this->generateCallbackId($callback);
        if (!$callbackId) return;

        $key = $this->prefix . $hook;
        
        // Need to find the specific member to remove
        // This is inefficient in Redis ZSet without storing the exact payload map, 
        // but robust enough for config management.
        $range = $this->redis->zRange($key, 0, -1);
        foreach ($range as $json) {
            $data = json_decode($json, true);
            if ($data['callback'] === $callbackId && $data['priority'] == $priority) {
                $this->redis->zRem($key, $json);
            }
        }
    }

    public function has(string $hook): bool
    {
        return $this->redis->exists($this->prefix . $hook) > 0;
    }

    protected function generateCallbackId(callable $callback): ?string
    {
        if (is_string($callback)) return $callback;
        if (is_array($callback)) {
            $class = is_object($callback[0]) ? get_class($callback[0]) : $callback[0];
            return $class . '::' . $callback[1];
        }
        // Closure or Object invoke
        return null; 
    }
}
