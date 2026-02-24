<?php

declare(strict_types=1);

namespace Modules\ReminderManager;

use App\Support\ServiceProvider;
use Modules\ReminderManager\Services\ReminderService;

class ReminderManagerServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->singleton(ReminderService::class, fn($app) => new ReminderService($app));
    }

    public function boot(): void
    {
        // Try to register commands by extending the kernel before handling
        if ($this->app->has(\Witals\Framework\Console\Kernel::class)) {
            $kernel = $this->app->make(\Witals\Framework\Console\Kernel::class);
            $kernel->register(Commands\ScanRemindersCommand::class);
        }
    }
}
