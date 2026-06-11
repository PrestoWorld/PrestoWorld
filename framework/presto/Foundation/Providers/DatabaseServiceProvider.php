<?php

declare(strict_types=1);

namespace PrestoWorld\Foundation\Providers;

use Witals\Framework\Support\ServiceProvider;
use Cycle\Database\Config;
use Cycle\Database\DatabaseManager;
use Cycle\Database\DatabaseInterface;
use Cycle\Database\Driver\SQLite\SQLiteDriver;
use Cycle\Database\Driver\MySQL\MySQLDriver;

class DatabaseServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(DatabaseInterface::class, function ($app) {
            $connection = getenv('DB_CONNECTION') ?: 'sqlite';
            
            $drivers = [
                'sqlite' => new Config\SQLiteDriverConfig(
                    connection: new Config\SQLite\FileConnectionConfig(
                        database: $app->basePath('storages/database.sqlite')
                    ),
                ),
                'mysql' => new Config\MySQLDriverConfig(
                    connection: new Config\MySQL\TcpConnectionConfig(
                        database: getenv('DB_DATABASE') ?: 'prestoworld',
                        host: getenv('DB_HOST') ?: '127.0.0.1',
                        port: (int) (getenv('DB_PORT') ?: 3306),
                        user: getenv('DB_USERNAME') ?: 'root',
                        password: getenv('DB_PASSWORD') ?: 'root',
                    ),
                ),
            ];

            $dbal = new DatabaseManager(
                new Config\DatabaseConfig([
                    'default' => 'default',
                    'databases' => [
                        'default' => ['driver' => $connection],
                    ],
                    'drivers' => $drivers,
                ])
            );

            return $dbal->database('default');
        });
    }
}
