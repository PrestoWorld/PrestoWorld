<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Witals\Framework\Console\Command;
use Cycle\Database\DatabaseProviderInterface;
use Cycle\Database\DatabaseInterface;

class DbCopyCommand extends Command
{
    protected string $name = 'db:copy';
    protected string $description = 'Copy PrestoWorld tables from one database connection to another';
    protected array $options = [
        '--from=CONNECTION' => 'Source database connection (default: wordpress)',
        '--to=CONNECTION' => 'Target database connection (default: presto)',
        '--prefix=STRING' => 'Table prefix filter (default: wp_ for wordpress, pw_ for presto)',
    ];

    public function handle(array $args): int
    {
        $dbal = app(DatabaseProviderInterface::class);
        $from = $this->getOption($args, 'from', 'wordpress');
        $to = $this->getOption($args, 'to', 'presto');

        if (!$dbal->hasDatabase($from)) {
            $this->error("Source database '{$from}' is not configured.");
            $this->line('Available: ' . implode(', ', $this->getDatabaseNames($dbal)));
            return 1;
        }

        if (!$dbal->hasDatabase($to)) {
            $this->error("Target database '{$to}' is not configured.");
            $this->line('Available: ' . implode(', ', $this->getDatabaseNames($dbal)));
            return 1;
        }

        $sourceDb = $dbal->database($from);
        $targetDb = $dbal->database($to);

        $prefix = $this->getOption($args, 'prefix', '');
        if ($prefix === '') {
            $prefix = $from === 'wordpress' ? 'wp_' : 'pw_';
        }

        $this->info("Copying tables from '{$from}' to '{$to}'...");
        $this->line("  Table prefix filter: '{$prefix}'");
        $this->line('');

        $sourceTables = $this->getPrefixedTables($sourceDb, $prefix);
        if ($sourceTables === []) {
            $this->warn("  No tables found with prefix '{$prefix}' in '{$from}'.");
            return 0;
        }

        $this->line('  Found ' . count($sourceTables) . ' table(s) to copy:');
        foreach ($sourceTables as $table) {
            $this->line("    - {$table}");
        }

        if (!$this->hasOption($args, 'force', 'f')) {
            $this->line('');
            $this->warn('  This is a dry run. Use --force to execute.');
            return 0;
        }

        return 0;
    }

    private function getDatabaseNames(DatabaseProviderInterface $dbal): array
    {
        $names = [];
        $ref = new \ReflectionMethod($dbal, 'database');
        if ($dbal instanceof \Cycle\Database\DatabaseManager) {
            $config = $dbal->getConfig();
            foreach ($config->getDatabases() as $name => $db) {
                $names[] = $name;
            }
        }
        return $names;
    }

    private function getPrefixedTables(DatabaseInterface $db, string $prefix): array
    {
        $all = $db->getTables();
        $matched = [];
        foreach ($all as $table) {
            $name = $table->getName();
            if (str_starts_with($name, $prefix)) {
                $matched[] = $name;
            }
        }
        sort($matched);
        return $matched;
    }
}
