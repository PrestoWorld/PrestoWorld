<?php

declare(strict_types=1);

namespace PrestoWorld\Hooks\Registries;

use PrestoWorld\Contracts\Hooks\Registries\HookRegistryInterface;
use PrestoWorld\Contracts\Hooks\HookStateType;
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
        
        try {
            $this->collection->createIndex(['type' => 1, 'hook' => 1, 'priority' => 1]);
        } catch (\Throwable $e) {}
    }

    public function set(string $type, string $hook, string $callback, int $priority, HookStateType $stateType = HookStateType::VOLATILE): void
    {
        $this->collection->updateOne(
            ['type' => $type, 'hook' => $hook, 'callback' => $callback, 'priority' => $priority],
            ['$set' => [
                'type' => $type,
                'hook' => $hook,
                'callback' => $callback,
                'priority' => $priority,
                'state_type' => $stateType->value
            ]],
            ['upsert' => true]
        );
    }

    public function get(string $type, string $hook): array
    {
        $cursor = $this->collection->find(
            ['type' => $type, 'hook' => $hook],
            ['sort' => ['priority' => 1]]
        );

        return iterator_to_array($cursor);
    }

    public function remove(string $type, string $hook, string $callback, int $priority): void
    {
        $this->collection->deleteOne([
            'type' => $type,
            'hook' => $hook,
            'callback' => $callback,
            'priority' => $priority
        ]);
    }

    public function clear(string $type, string $hook, ?int $priority = null): void
    {
        $filter = ['type' => $type, 'hook' => $hook];
        if ($priority !== null) {
            $filter['priority'] = $priority;
        }
        $this->collection->deleteMany($filter);
    }

    public function has(string $type, string $hook): bool
    {
        return $this->collection->countDocuments(['type' => $type, 'hook' => $hook]) > 0;
    }
}
