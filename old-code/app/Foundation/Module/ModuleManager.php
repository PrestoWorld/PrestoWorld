<?php

declare(strict_types=1);

namespace App\Foundation\Module;

use App\Foundation\Application;

class ModuleManager
{
    private Application $app;
    private array $modules = [];
    private array $loaded = [];

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function discover(): void
    {
        $modulesPath = $this->app->basePath('modules');

        if (!is_dir($modulesPath)) {
            return;
        }

        foreach (scandir($modulesPath) as $dir) {
            if ($dir === '.' || $dir === '..') {
                continue;
            }

            $modulePath = $modulesPath . '/' . $dir;
            $metadataPath = $modulePath . '/module.json';

            if (file_exists($metadataPath)) {
                $metadata = json_decode(file_get_contents($metadataPath), true);

                $configKey = "modules.enabled.{$metadata['name']}";
                if ($this->app->config($configKey) !== null) {
                    $metadata['enabled'] = $this->app->config($configKey);
                }

                $this->modules[$metadata['name']] = new Module($this->app, $modulePath, $metadata);
            }
        }
    }

    public function loadEnabled(): void
    {
        $sorted = $this->getSortedModules();

        foreach ($sorted as $module) {
            if ($module->isEnabled()) {
                $this->load($module);
            }
        }
    }

    public function load(ModuleInterface $module): void
    {
        $name = $module->getName();

        if (isset($this->loaded[$name])) {
            return;
        }

        $metadata = json_decode(file_get_contents($module->getPath() . '/module.json'), true);
        if (isset($metadata['autoload']['psr-4'])) {
            foreach ($metadata['autoload']['psr-4'] as $ns => $path) {
                $libPath = $module->getPath() . '/' . $path;
                spl_autoload_register(function ($class) use ($ns, $libPath) {
                    if (str_starts_with($class, $ns)) {
                        $relative = substr($class, strlen($ns));
                        $file = $libPath . str_replace('\\', '/', $relative) . '.php';
                        if (file_exists($file)) {
                            include $file;
                        }
                    }
                });
            }
        }

        $module->boot();
        $this->loaded[$name] = true;
    }

    private function getSortedModules(): array
    {
        $modules  = $this->modules;
        $sorted   = [];
        $visiting = [];
        $visited  = [];

        $visit = null;
        $visit = function (string $name) use (&$modules, &$sorted, &$visiting, &$visited, &$visit): void {
            if (isset($visited[$name])) {
                return;
            }

            if (isset($visiting[$name])) {
                throw new \RuntimeException(
                    "ModuleManager: Circular dependency detected involving module '{$name}'."
                );
            }

            $visiting[$name] = true;

            $module = $modules[$name] ?? null;
            if ($module !== null) {
                foreach ($module->getDependencies() as $depName) {
                    if (!isset($modules[$depName])) {
                        error_log("ModuleManager: Module '{$name}' depends on '{$depName}' which is not installed.");
                        continue;
                    }
                    $visit($depName);
                }
            }

            unset($visiting[$name]);
            $visited[$name] = true;

            if ($module !== null) {
                $sorted[] = $module;
            }
        };

        $prioritised = array_values($modules);
        usort($prioritised, function (ModuleInterface $a, ModuleInterface $b) {
            if ($a->getPriority() !== $b->getPriority()) {
                return $a->getPriority() <=> $b->getPriority();
            }
            return strcmp($a->getName(), $b->getName());
        });

        foreach ($prioritised as $module) {
            $visit($module->getName());
        }

        return $sorted;
    }

    public function all(): array
    {
        return $this->modules;
    }

    public function allSorted(): array
    {
        return $this->getSortedModules();
    }

    public function isLoaded(string $name): bool
    {
        return isset($this->loaded[$name]);
    }
}
