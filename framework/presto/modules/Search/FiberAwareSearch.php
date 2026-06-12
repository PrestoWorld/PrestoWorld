<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\Search;

use Prestoworld\SearchEngine\SearchEngine;
use Prestoworld\SearchEngine\SearchResult;
use Witals\Framework\Contracts\ConcurrentManager;

/**
 * Concurrent search dispatcher using PHP Fibers via ConcurrentManager.
 *
 * In long-running runtimes (RoadRunner), multiple independent search
 * queries overlap within a single request — the event loop suspends a
 * fiber when its DB/API query is pending and resumes it when data arrives.
 *
 * Usage:
 *   $results = FiberAwareSearch::all([
 *       'recent_posts' => ['post_type' => 'post', 'posts_per_page' => 5],
 *       'featured'     => ['post_type' => 'post', 'posts_per_page' => 3, 'tag_id' => 7],
 *   ]);
 *
 * Fallback (traditional runtime): queries execute sequentially with zero
 * fiber overhead.
 */
class FiberAwareSearch
{
    /**
     * Execute multiple search queries concurrently using Fibers.
     *
     * @param array<string, array>      $queries  key => query args
     * @param SearchEngine|null         $engine   shared engine instance
     * @param ConcurrentManager|null    $concurrent  fiber manager
     * @return array<string, SearchResult>
     */
    public static function all(
        array $queries,
        ?SearchEngine $engine = null,
        ?ConcurrentManager $concurrent = null,
    ): array {
        $engine ??= app(SearchEngine::class);
        $concurrent ??= app(ConcurrentManager::class);

        $tasks = [];
        foreach ($queries as $key => $args) {
            $tasks[$key] = fn() => $engine->search($args);
        }

        return $concurrent->all($tasks);
    }

    /**
     * Execute a single search query wrapped in a Fiber.
     */
    public static function single(
        array $args,
        ?SearchEngine $engine = null,
        ?ConcurrentManager $concurrent = null,
    ): SearchResult {
        $engine ??= app(SearchEngine::class);
        $result = null;

        $fn = function () use ($engine, $args, &$result) {
            $result = $engine->search($args);
        };

        ($concurrent ?? app(ConcurrentManager::class))->run($fn);

        return $result ?? new SearchResult([], 0);
    }
}
