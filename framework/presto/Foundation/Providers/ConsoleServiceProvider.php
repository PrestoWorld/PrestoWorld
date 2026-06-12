<?php

declare(strict_types=1);

namespace PrestoWorld\Foundation\Providers;

use Witals\Framework\Support\ServiceProvider;
use Witals\Framework\Console\Kernel;
use PrestoWorld\Foundation\Console\Commands\MakeModuleCommand;
use PrestoWorld\Foundation\Console\Commands\MakeBlockCommand;
use PrestoWorld\Foundation\Console\Commands\MakeCommandCommand;
use PrestoWorld\Foundation\Console\Commands\MakeProviderCommand;

class ConsoleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->extend(Kernel::class, function (Kernel $kernel) {
            $kernel->register(MakeModuleCommand::class);
            $kernel->register(MakeBlockCommand::class);
            $kernel->register(MakeCommandCommand::class);
            $kernel->register(MakeProviderCommand::class);
            return $kernel;
        });
    }
}
