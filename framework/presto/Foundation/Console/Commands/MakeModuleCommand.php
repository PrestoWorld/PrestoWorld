<?php

declare(strict_types=1);

namespace PrestoWorld\Foundation\Console\Commands;

class MakeModuleCommand extends MakeCommand
{
    protected string $name = 'make:module';
    protected string $description = 'Create a new PrestoWorld module';
    protected string $type = 'Module';
    protected array $arguments = ['name' => 'The name of the module (e.g., Blog)'];

    protected function getStub(): string
    {
        return <<<'PHP'
<?php

declare(strict_types=1);

namespace {{ namespace }};

use Witals\Framework\Module\Module as WitalsModule;

class Module extends WitalsModule
{
    public function register(): void
    {
        // Bind module services here
        // $this->app->singleton(YourService::class, fn() => new YourService());
    }

    public function boot(): void
    {
        // Boot logic here
    }
}
PHP;
    }

    protected function getPath(string $name): string
    {
        return $this->app->basePath() . "/framework/presto/modules/{$name}/Module.php";
    }

    protected function getNamespace(string $name): string
    {
        return "PrestoWorld\\Modules\\{$name}";
    }
}
