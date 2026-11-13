<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Witals\Framework\Console\Command;
use PrestoWorld\Database\SchemaMigrationManager;

class MigrateCommand extends Command
{
    protected string $name = 'migrate';
    protected string $description = 'Run database migrations';

    public function handle(array $args): int
    {
        $force = $this->hasOption($args, 'force');
        $customPath = $this->getOption($args, 'path');

        if (!$force && getenv('APP_ENV') === 'production') {
            $this->error('Cannot run migrations in production. Use --force to override.');
            return 1;
        }

        $db = app(\Cycle\Database\DatabaseInterface::class);
        if (!($this->app instanceof \App\Foundation\Application)) {
            $this->error('The application does not support module migrations.');
            return 1;
        }

        if ($customPath) {
            $fullPath = $this->app->basePath() . '/' . ltrim($customPath, '/');
            if (!is_dir($fullPath)) {
                $this->error("Migration path not found: {$fullPath}");
                return 1;
            }
            $manager = new SchemaMigrationManager(
                $db,
                $this->app->basePath(),
                [$fullPath],
            );
            $this->info("Running migrations from: {$customPath}");
        } else {
            $manager = new SchemaMigrationManager(
                $db,
                $this->app->basePath(),
                $this->app->moduleMigrationPaths(),
            );
            $this->info('Running migrations...');
        }

        $result = $manager->runMigrations($force);

        foreach ($result['created'] as $name) {
            $this->line("  [<fg=green>OK</>] {$name}");
        }
        foreach ($result['skipped'] as $name) {
            $this->line("  [<fg=yellow>SKIP</>] {$name} (already executed)");
        }

        $this->info('Migration complete!');

        return 0;
    }

    protected function getOption(array $args, string $name, mixed $default = null): mixed
    {
        $options = $this->parseOptions($args);
        return $options[$name] ?? $default;
    }
}
