<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Witals\Framework\Console\Command;
use PrestoWorld\Foundation\Database\SchemaVerifier;
use Cycle\Database\DatabaseInterface;

class DbVerifyCommand extends Command
{
    protected string $name = 'db:verify';
    protected string $description = 'Verify database schema integrity — check that all required PrestoWorld tables exist';

    public function handle(array $args): int
    {
        $forceFresh = $this->hasOption($args, 'force', 'f');
        $prefix = getenv('PW_TABLE_PREFIX') ?: 'pw_';

        $this->info('Verifying database schema...');
        $this->line("  Prefix: {$prefix}");
        $this->line('');

        $db = app(DatabaseInterface::class);
        $storagePath = $this->app->storagePath();

        $verifier = new SchemaVerifier($db, $prefix, $storagePath);
        $result = $verifier->verify($forceFresh);

        if ($result['healthy']) {
            $this->info("  [OK] All {$result['total']} required tables exist.");
            return 0;
        }

        $this->error("  [FAIL] " . count($result['missing']) . " table(s) are missing:");
        foreach ($result['missing'] as $table) {
            $this->line("    - {$table}");
        }

        $this->line('');
        $this->line("  {$result['existing']}/{$result['total']} tables exist.");
        $this->line('  Run `php presto migrate` to create missing tables.');

        return 1;
    }
}
