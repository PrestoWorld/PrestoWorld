<?php

declare(strict_types=1);

namespace App\Providers;

use Witals\Framework\Support\ServiceProvider;
use Witals\Framework\Console\Kernel;
use App\Console\Commands\SeedCommand;
use App\Console\Commands\DbVerifyCommand;
use App\Console\Commands\DbCopyCommand;
use App\Console\Commands\DbInitCommand;
use App\Console\Commands\MigrateCommand;

class ConsoleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->extend(Kernel::class, function (Kernel $kernel) {
            $kernel->register(SeedCommand::class);
            $kernel->register(DbVerifyCommand::class);
            $kernel->register(DbCopyCommand::class);
            $kernel->register(DbInitCommand::class);
            $kernel->register(MigrateCommand::class);
            return $kernel;
        });
    }
}
