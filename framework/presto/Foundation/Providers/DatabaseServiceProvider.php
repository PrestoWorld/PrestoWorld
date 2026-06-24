<?php

declare(strict_types=1);

namespace PrestoWorld\Foundation\Providers;

use Witals\Framework\Support\ServiceProvider;
use Cycle\Database\Config;
use Cycle\Database\DatabaseManager;
use Cycle\Database\DatabaseInterface;

class DatabaseServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(DatabaseManager::class, function ($app) {
            $drivers = [];
            $databases = [];

            // ── Native PrestoWorld: PostgreSQL (production) ──
            $drivers['presto_pgsql'] = new Config\PostgresDriverConfig(
                connection: new Config\Postgres\TcpConnectionConfig(
                    database: getenv('DB_PGSQL_DATABASE') ?: 'prestoworld',
                    host: getenv('DB_PGSQL_HOST') ?: '127.0.0.1',
                    port: (int) (getenv('DB_PGSQL_PORT') ?: 5432),
                    user: getenv('DB_PGSQL_USERNAME') ?: 'prestoworld',
                    password: getenv('DB_PGSQL_PASSWORD') ?: 'prestoworld',
                ),
            );
            $databases['presto_pgsql'] = ['driver' => 'presto_pgsql'];

            // ── SQLite fallback (development) ──
            $sqlitePath = $app->basePath('storage/database.sqlite');
            if (file_exists($sqlitePath)) {
                $drivers['presto_sqlite'] = new Config\SQLiteDriverConfig(
                    connection: new Config\SQLite\FileConnectionConfig(
                        database: $sqlitePath,
                    ),
                );
                $databases['presto_sqlite'] = ['driver' => 'presto_sqlite'];
            }

            // ── Legacy WordPress: MySQL ──
            $mysqlDb = null;
            $mysqlHost = '127.0.0.1';
            $mysqlPort = 3306;
            $mysqlUser = 'root';
            $mysqlPass = 'root';

            if ($app->has(\PrestoWorld\Bridge\WordPress\WordPressConfig::class)) {
                $wpConfig = $app->make(\PrestoWorld\Bridge\WordPress\WordPressConfig::class);
                $mysqlDb = $wpConfig['DB_DATABASE'] ?? $wpConfig['DB_NAME'] ?? null;
                $mysqlHost = $wpConfig['DB_HOST'] ?? '127.0.0.1';
                $mysqlPort = (int) ($wpConfig['DB_PORT'] ?? 3306);
                $mysqlUser = $wpConfig['DB_USERNAME'] ?? $wpConfig['DB_USER'] ?? 'root';
                $mysqlPass = $wpConfig['DB_PASSWORD'] ?? 'root';
            } else {
                $mysqlDb = getenv('DB_MYSQL_DATABASE');
                $mysqlHost = getenv('DB_MYSQL_HOST') ?: '127.0.0.1';
                $mysqlPort = (int) (getenv('DB_MYSQL_PORT') ?: 3306);
                $mysqlUser = getenv('DB_MYSQL_USERNAME') ?: 'root';
                $mysqlPass = getenv('DB_MYSQL_PASSWORD') ?: 'root';
            }

            if ($mysqlDb) {
                $drivers['wordpress_mysql'] = new Config\MySQLDriverConfig(
                    connection: new Config\MySQL\TcpConnectionConfig(
                        database: $mysqlDb,
                        host: $mysqlHost,
                        port: $mysqlPort,
                        user: $mysqlUser,
                        password: $mysqlPass,
                    ),
                );
                $databases['wordpress'] = ['driver' => 'wordpress_mysql'];
            }

            // ── Default 'presto' alias: SQLite > PostgreSQL ──
            $databases['presto'] = isset($databases['presto_sqlite'])
                ? $databases['presto_sqlite']
                : $databases['presto_pgsql'];

            return new DatabaseManager(
                new Config\DatabaseConfig([
                    'default' => 'presto',
                    'databases' => $databases,
                    'drivers' => $drivers,
                ])
            );
        });

        $this->app->singleton(\Cycle\Database\DatabaseProviderInterface::class, function ($app) {
            return $app->make(DatabaseManager::class);
        });

        $this->app->singleton(DatabaseInterface::class, function ($app) {
            return $app->make(DatabaseManager::class)->database('presto');
        });
    }
}
