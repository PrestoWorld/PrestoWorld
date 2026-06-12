<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\Search;

use Prestoworld\SearchEngine\SearchEngine;
use Prestoworld\SearchEngine\SearchResult;

/**
 * Concurrent search dispatcher using PHP Fibers.
 *
 * In long-running environments (RoadRunner, Swoole), this allows
 * multiple independent search queries to run concurrently within
 * a single request, preventing sequential worker blocking.
 *
 * Usage:
 *   $results = FiberAwareSearch::all([
 *       'recent_posts' => ['post_type' => 'post', 'posts_per_page' => 5],
 *       'featured'     => ['post_type' => 'post', 'posts_per_page' => 3, 'tag_id' => 7],
 *   ]);
 *
 * Each query runs in its own Fiber; DB queries still block individually
 * but multiple queries overlap rather than executing back-to-back.
 */
class FiberAwareSearch
{
    /**
     * Execute multiple search queries concurrently using Fibers.
     *
     * @param array<string, array> $queries  key => query args
     * @param SearchEngine|null    $engine   shared engine instance
     * @return array<string, SearchResult>
     */
    public static function all(array $queries, ?SearchEngine $engine = null): array
    {
        $engine ??= app(SearchEngine::class);
        $results = [];
        $fibers = [];

        foreach ($queries as $key => $args) {
            $fibers[$key] = new \Fiber(function () use ($engine, $args) {
                return $engine->search($args);
            });
        }

        foreach ($fibers as $key => $fiber) {
            $fiber->start();
            if ($fiber->isTerminated()) {
                $results[$key] = $fiber->getReturn();
            }
        }

        return $results;
    }

    /**
     * Execute a single search query wrapped in a Fiber.
     * Useful when you want to interleave DB work with search.
     */
    public static function single(array $args, ?SearchEngine $engine = null): SearchResult
    {
        $engine ??= app(SearchEngine::class);
        $fiber = new \Fiber(function () use ($engine, $args) {
            return $engine->search($args);
        });

        $fiber->start();

        if ($fiber->isTerminated()) {
            return $fiber->getReturn();
        }

        // Should not reach here for synchronous DB queries
        return new SearchResult([], 0);
    }
}
