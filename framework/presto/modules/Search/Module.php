<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\Search;

use Prestoworld\SearchEngine\SearchEngine;
use Prestoworld\SearchEngine\SearchManager;
use Prestoworld\SearchEngine\Adapters\TypesenseAdapter;
use Prestoworld\SearchEngine\Adapters\MeilisearchAdapter;
use Prestoworld\SearchEngine\Adapters\TNTSearchAdapter;
use Witals\Framework\Http\Client\ConcurrentHttpClient;
use Witals\Framework\Contracts\ConcurrentManager;
use Witals\Framework\Module\Module as WitalsModule;
use PrestoWorld\Modules\Schema\PostRepository;
use Cycle\Database\DatabaseInterface;

class Module extends WitalsModule
{
    private static bool $helpersLoaded = false;

    public function __construct(
        protected \Witals\Framework\Application $app,
        protected string $path = '',
        protected array $metadata = [],
    ) {
        if ($path === '') {
            $path = __DIR__;
        }
        if ($metadata === []) {
            $metadata = ['name' => 'search'];
        }
        parent::__construct($app, $path, $metadata);
    }

    public function getName(): string
    {
        return 'PrestoWorld Search Engine Module';
    }

    public function register(): void
    {
        // Primary: DB-backed SearchEngine (fast, no external I/O)
        $this->app->singleton(SearchEngine::class, function ($app) {
            return new SearchEngine(
                $app->make(DatabaseInterface::class),
                $app->make(PostRepository::class)
            );
        });

        // HTTP client for concurrent external search requests
        $this->app->singleton(ConcurrentHttpClient::class, function ($app) {
            return new ConcurrentHttpClient(
                client: null,
                defaultOptions: ['timeout' => 5, 'max_duration' => 10],
                concurrent: $app->make(ConcurrentManager::class),
            );
        });

        // Register adapter-based SearchManager (lazy – no HTTP client created until used)
        $this->app->singleton(SearchManager::class, function ($app) {
            $config = $app->config('search', []);

            return new SearchManager($config);
        });

        // Register specific adapters as injectable services
        $this->app->bind(TypesenseAdapter::class, function ($app) {
            $config = $app->config('search.adapters.typesense', []);
            return new TypesenseAdapter($config);
        });

        $this->app->bind(MeilisearchAdapter::class, function ($app) {
            $config = $app->config('search.adapters.meilisearch', []);
            return new MeilisearchAdapter($config);
        });

        $this->app->bind(TNTSearchAdapter::class, function () {
            return new TNTSearchAdapter();
        });

        // Global Helper for PW_Query — loaded once
        if (!self::$helpersLoaded) {
            require_once __DIR__ . '/helpers.php';
            self::$helpersLoaded = true;
        }
    }

    public function boot(): void
    {
        // No eager initialization — adapters connect lazily on first use.
        // In RoadRunner this means zero I/O during worker boot.
    }
}
