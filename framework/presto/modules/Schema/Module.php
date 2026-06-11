<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\Schema;

use Witals\Framework\Module\BaseModule;
use Cycle\Database\DatabaseInterface;

class Module extends BaseModule
{
    public function getName(): string
    {
        return 'PrestoWorld Schema Engine';
    }

    public function register(): void
    {
        $this->app->singleton(PostTypeSchemaManager::class, function($app) {
            return new PostTypeSchemaManager(
                $app->make(DatabaseInterface::class),
                $app->basePath('storages/framework/cache')
            );
        });

        // Load compatibility helpers
        require_once __DIR__ . '/helpers.php';
    }

    public function boot(): void
    {
        // Initial schema check or logging could go here
    }
}
