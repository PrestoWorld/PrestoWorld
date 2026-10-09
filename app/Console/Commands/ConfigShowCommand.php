<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Witals\Framework\Console\Command;
use App\Config\ConfigManager;

class ConfigShowCommand extends Command
{
    protected string $name = 'config:show';
    protected string $description = 'Show configuration info and active config reader';

    /**
     * @param list<string> $args
     */
    public function handle(array $args): int
    {
        /** @var ConfigManager $config */
        $config = $this->app->make(ConfigManager::class);

        $this->info('Configuration Info');
        $this->line('');

        $this->line('Active Reader: ' . $config->getActiveReaderName());
        $this->line('Is WordPress Config: ' . ($config->isWordPressConfig() ? 'yes' : 'no'));
        $this->line('Is Env Config: ' . ($config->isEnvConfig() ? 'yes' : 'no'));
        $this->line('');

        $this->line('Database Config:');
        $dbConfig = $config->getDatabaseConfig();
        foreach ($dbConfig as $key => $value) {
            $this->line("  {$key}: " . (is_bool($value) ? ($value ? 'true' : 'false') : $value));
        }
        $this->line('');

        $this->line('Theme Config:');
        $themeConfig = $config->getThemeConfig();
        foreach ($themeConfig as $key => $value) {
            $this->line("  {$key}: " . (is_bool($value) ? ($value ? 'true' : 'false') : $value));
        }
        $this->line('');

        $this->line('Table Prefix: ' . $config->getTablePrefix());
        $this->line('Debug Mode: ' . ($config->isDebug() ? 'enabled' : 'disabled'));
        $this->line('');

        $this->line('Config Readers:');
        $diagnostics = $config->getDiagnostics();
        foreach ($diagnostics['readers'] as $name => $info) {
            $status = $info['supports'] ? '✓' : '✗';
            $this->line("  [{$status}] {$name} (priority: {$info['priority']})");
        }

        return 0;
    }
}
