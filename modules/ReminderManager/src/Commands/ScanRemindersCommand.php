<?php

declare(strict_types=1);

namespace Modules\ReminderManager\Commands;

use Witals\Framework\Console\Command;
use Modules\ReminderManager\Services\ReminderService;

class ScanRemindersCommand extends Command
{
    protected string $name = 'reminders:scan';
    protected string $description = 'Scan all services for upcoming expirations and send notifications';

    public function handle(array $args): int
    {
        $this->info("Starting service reminder scan...\n");
        
        // Manual dependency resolution as the handle method signature in this framework seems strict for array $args
        $service = app(ReminderService::class);
        $stats = $service->scanAndNotify();

        $this->info("Scan complete!\n");
        $this->line("Summary of reminders sent:");
        $this->line("- Licenses: " . $stats['licenses']);
        $this->line("- Domains: " . $stats['domains']);
        $this->line("- Hosting: " . $stats['hosting']);
        $this->line("- Web Services (Warranty): " . $stats['web_services']);
        
        return 0;
    }
}
