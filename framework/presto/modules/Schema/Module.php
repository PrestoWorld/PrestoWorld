<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\Schema;

use Witals\Framework\Module\Module as WitalsModule;
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
            $metadata = ['name' => 'schema'];
        }
        parent::__construct($app, $path, $metadata);
    }

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
        // Register console commands
        if ($this->app->has(\Witals\Framework\Console\Kernel::class)) {
            $this->app->make(\Witals\Framework\Console\Kernel::class)
                ->register(\App\Console\Commands\DemoSchemaCommand::class);
        }
    }
}
