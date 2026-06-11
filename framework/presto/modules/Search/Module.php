<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\Search;

use Witals\Framework\Module\Module as WitalsModule;
use PrestoWorld\Modules\Schema\PostRepository;
use Cycle\Database\DatabaseInterface;

class Module extends WitalsModule
{
    public function __construct($app)
    {
        parent::__construct($app, __DIR__, ['name' => 'search']);
    }

    public function getName(): string
    {
        return 'PrestoWorld Search Engine Module';
    }

    public function register(): void
    {
        $this->app->singleton(SearchEngine::class, function($app) {
            return new SearchEngine(
                $app->make(DatabaseInterface::class),
                $app->make(PostRepository::class)
            );
        });

        // Global Helper for PW_Query
        require_once __DIR__ . '/helpers.php';
    }
}
