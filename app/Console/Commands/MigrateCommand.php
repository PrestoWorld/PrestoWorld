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

        if (!$force && getenv('APP_ENV') === 'production') {
            $this->error('Cannot run migrations in production. Use --force to override.');
            return 1;
        }

        $db = app(\Cycle\Database\DatabaseInterface::class);
        $manager = new SchemaMigrationManager($db, $this->app->basePath());

        $this->info('Running migrations...');
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
}
