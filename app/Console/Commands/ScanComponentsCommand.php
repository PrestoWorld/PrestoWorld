<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\ComponentScannerService;
use Witals\Framework\Console\Command;

class ScanComponentsCommand extends Command
{
    protected string $name = 'components:scan';
    protected string $description = 'Scan plugins, themes and modules via Service';
    protected array $options = [
        '--force, -f' => 'Force scan even if cache exists'
    ];

    public function handle(array $args): int
    {
        $force = $this->hasOption($args, 'force', 'f');
        $scanner = app(ComponentScannerService::class);

        $this->info("\n🔍 Starting components scan (" . ($force ? "forced" : "cached") . ")...\n");

        try {
            $results = $scanner->scan($force);
            
            $this->info("📁 Plugins:");
            foreach ($results['plugins'] as $item) {
                $typeStr = $item['is_wordpress'] ? '<fg=yellow>WordPress</>' : '<fg=green>PrestoWorld</>';
                $this->line("   - {$item['name']} [{$typeStr}]");
            }

            $this->info("\n📁 Themes:");
            foreach ($results['themes'] as $item) {
                $typeStr = $item['is_wordpress'] ? '<fg=yellow>WordPress</>' : '<fg=green>PrestoWorld</>';
                $this->line("   - {$item['name']} [{$typeStr}]");
            }

            $this->info("\n📁 Modules:");
            foreach ($results['modules'] as $item) {
                $this->line("   - {$item['name']} [<fg=green>Native</>]");
            }

            $this->info("\n✅ Scanned at: {$results['scanned_at']}");
            $this->info("✅ Scan and database sync completed successfully!\n");

        } catch (\Throwable $e) {
            $this->error("❌ Scan failed: " . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
