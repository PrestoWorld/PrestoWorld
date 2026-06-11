<?php

declare(strict_types=1);

namespace App\Providers;

use App\Support\ServiceProvider;
use Psr\Log\LoggerInterface;
use Witals\Framework\Log\LogManager;

class LogServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->singleton(LoggerInterface::class, function ($app) {
            return new LogManager([
                'default' => 'standard',
                'channels' => [
                    'standard' => [
                        'driver' => 'standard',
                        'path' => $app->basePath('storage/logs/prestoworld.log'),
                        'level' => 'info',
                        'buffered' => true,
                        'formatter' => 'line',
                    ],
                    'debug' => [
                        'driver' => 'debug',
                        'level' => 'debug',
                    ],
                    'null' => [
                        'driver' => 'null',
                    ],
                ],
            ]);
        });
    }
}
