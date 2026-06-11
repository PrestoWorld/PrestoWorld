<?php

declare(strict_types=1);

namespace App\Foundation;

use Witals\Framework\Application as BaseApplication;
use App\Foundation\Module\ModuleManager;
use Witals\Framework\Console\ConsoleServiceProvider;
use Witals\Framework\Queue\QueueServiceProvider;

class Application extends BaseApplication
{
    protected ?ModuleManager $moduleManager = null;
    protected array $config = [];

    protected function registerCoreContainerAliases(): void
    {
        parent::registerCoreContainerAliases();

        $this->moduleManager = new ModuleManager($this);
        $this->instance(ModuleManager::class, $this->moduleManager);
    }

    protected function registerConfiguredProviders(): void
    {
        $this->register(ConsoleServiceProvider::class);
        $this->register(QueueServiceProvider::class);
    }

    public function boot(): void
    {
        if ($this->booted) {
            return;
        }

        $this->bootstrap();

        if ($this->moduleManager) {
            $this->moduleManager->discover();
            $this->moduleManager->loadEnabled();
        }

        parent::boot();
    }

    public function config(string $key, $default = null)
    {
        $keys = explode('.', $key);
        $file = array_shift($keys);

        $configPath = $this->basePath("config/{$file}.php");

        if (!file_exists($configPath)) {
            return $default;
        }

        if (isset($this->config[$file])) {
            $config = $this->config[$file];
        } else {
            $config = require $configPath;
            $this->config[$file] = $config;
        }

        foreach ($keys as $segment) {
            if (!is_array($config) || !array_key_exists($segment, $config)) {
                return $default;
            }
            $config = $config[$segment];
        }

        return $config;
    }

    public function getErrorLogPath(): string
    {
        return $this->basePath('storage/logs/prestoworld.log');
    }
}
