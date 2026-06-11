<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\Search;

use Witals\Framework\Module\BaseModule;
use PrestoWorld\Modules\Schema\PostRepository;
use Cycle\Database\DatabaseInterface;

class Module extends BaseModule
{
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
        if (!function_exists('pw_query')) {
            function pw_query(array $args = []): PW_Query
            {
                return new PW_Query($args);
            }
        }
    }
}
