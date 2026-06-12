<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\Search;

use Witals\Framework\Module\Module as WitalsModule;
use PrestoWorld\Modules\Schema\PostRepository;
use Cycle\Database\DatabaseInterface;

class Module extends WitalsModule
{
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
