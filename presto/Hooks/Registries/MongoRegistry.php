<?php

declare(strict_types=1);

namespace PrestoWorld\Hooks\Registries;

use PrestoWorld\Contracts\Hooks\Registries\HookRegistryInterface;
use MongoDB\Client;
use MongoDB\Collection;

class MongoRegistry implements HookRegistryInterface
{
    protected Client $client;
    protected Collection $collection;

    public function __construct(string $uri, string $database = 'presto_core', string $collection = 'hooks_registry')
    {
        $this->client = new Client($uri, [], [
            'typeMap' => [
                'root' => 'array', 
                'document' => 'array', 
                'array' => 'array'
            ]
        ]);
        $this->collection = $this->client->selectDatabase($database)->selectCollection($collection);
        
        // Ensure index for fast lookup
        // We only try to create index once, catch error if already exists or permissions issue
        try {
            $this->collection->createIndex(['hook_name' => 1, 'priority' => 1]);
        } catch (\Throwable $e) {
            // Ignore index creation errors in runtime
        }
    }

    public function register(string $hook, callable $callback, int $priority = 10, int $acceptedArgs = 1): void
    {
        $callbackId = $this->generateCallbackId($callback);
        if (!$callbackId) return;

        // In Mongo, we can store structured documents
        // We use updateOne with upsert to avoid duplicates for the same callback
        $this->collection->updateOne(
            [
                'hook_name' => $hook,
                'callback_id' => $callbackId,
                'priority' => $priority
            ],
            [
                '$set' => [
                    'hook_name' => $hook,
                    'callback_id' => $callbackId,
                    'priority' => $priority,
                    'accepted_args' => $acceptedArgs,
                    'updated_at' => new \MongoDB\BSON\UTCDateTime()
                ]
            ],
            ['upsert' => true]
        );
    }

    public function getCallbacks(string $hook): array
    {
        // Fetch sorted by priority ASC
        $cursor = $this->collection->find(
            ['hook_name' => $hook],
            ['sort' => ['priority' => 1]]
        );

        $callbacks = [];
        foreach ($cursor as $doc) {
            // Transform back to array format expected by Manager
            $callbacks[] = [
                'callback' => $doc['callback_id'],
                'priority' => $doc['priority'],
                'accepted_args' => $doc['accepted_args'] ?? 1
            ];
        }
        return $callbacks;
    }

    public function remove(string $hook, callable $callback, int $priority = 10): void
    {
        $callbackId = $this->generateCallbackId($callback);
        if (!$callbackId) return;

        $this->collection->deleteOne([
            'hook_name' => $hook,
            'callback_id' => $callbackId,
            'priority' => $priority
        ]);
    }

    public function has(string $hook): bool
    {
        $count = $this->collection->countDocuments(['hook_name' => $hook]);
        return $count > 0;
    }

    protected function generateCallbackId(callable $callback): ?string
    {
        if (is_string($callback)) return $callback;
        if (is_array($callback)) {
            $class = is_object($callback[0]) ? get_class($callback[0]) : $callback[0];
            return $class . '::' . $callback[1];
        }
        return null; 
    }
}
