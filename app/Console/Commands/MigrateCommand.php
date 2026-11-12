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
        $force = in_array('--force', $args, true);
        $customPath = $this->getOption($args, '--path');

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

    private function getOption(array $args, string $option): ?string
    {
        $index = array_search($option, $args, true);
        if ($index !== false && isset($args[$index + 1]) && !str_starts_with($args[$index + 1], '--')) {
            return $args[$index + 1];
        }
        if ($index !== false && str_contains($args[$index], '=')) {
            return substr($args[$index], strpos($args[$index], '=') + 1);
        }
        return null;
    }
}
