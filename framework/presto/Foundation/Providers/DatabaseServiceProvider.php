<?php

declare(strict_types=1);

namespace PrestoWorld\Foundation\Providers;

use Witals\Framework\Support\ServiceProvider;
use PrestoWorld\Bridge\WordPress\WordPressConfig;
use PrestoWorld\Foundation\Database\OptionRepository;
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
            $pgDb = getenv('DB_PGSQL_DATABASE');
            if ($pgDb) {
                $drivers['presto_pgsql'] = new Config\PostgresDriverConfig(
                    connection: new Config\Postgres\TcpConnectionConfig(
                        database: $pgDb,
                        host: getenv('DB_PGSQL_HOST') ?: '127.0.0.1',
                        port: (int) (getenv('DB_PGSQL_PORT') ?: 5432),
                        user: getenv('DB_PGSQL_USERNAME') ?: 'prestoworld',
                        password: getenv('DB_PGSQL_PASSWORD') ?: 'prestoworld',
                    ),
                );
                $databases['presto_pgsql'] = ['connection' => 'presto_pgsql'];
            }

            // ── Legacy WordPress: MySQL ──
            $wordPressDetected = $this->configureWordPressConnection($app, $drivers, $databases);

            // ── SQLite fallback (development, only when WordPress is NOT detected) ──
            if (!$wordPressDetected) {
                $sqlitePath = $app->basePath('storage/database.sqlite');
                if (file_exists($sqlitePath)) {
                    $drivers['presto_sqlite'] = new Config\SQLiteDriverConfig(
                        connection: new Config\SQLite\FileConnectionConfig(
                            database: $sqlitePath,
                        ),
                    );
                    $databases['presto_sqlite'] = ['connection' => 'presto_sqlite'];
                }
            }

            // ── Default 'presto' alias: PostgreSQL > WordPress > SQLite ──
            if (isset($databases['presto_pgsql'])) {
                $databases['presto'] = $databases['presto_pgsql'];
            } elseif (isset($databases['wordpress'])) {
                $databases['presto'] = $databases['wordpress'];
            } elseif (isset($databases['presto_sqlite'])) {
                $databases['presto'] = $databases['presto_sqlite'];
            } else {
                $drivers['presto_sqlite_mem'] = new Config\SQLiteDriverConfig(
                    connection: new Config\SQLite\MemoryConnectionConfig(),
                );
                $databases['presto'] = ['connection' => 'presto_sqlite_mem'];
            }

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

        $this->app->singleton(OptionRepository::class, function ($app) {
            return new OptionRepository($app->make(DatabaseInterface::class));
        });
    }

    private function configureWordPressConnection($app, array &$drivers, array &$databases): bool
    {
        $mysqlDb = null;
        $mysqlHost = '127.0.0.1';
        $mysqlPort = 3306;
        $mysqlUser = 'root';
        $mysqlPass = '';

        // The vendor wp-bridge BridgeServiceProvider stores WordPress config
        // as an array in the container under WordPressConfig::class
        if ($app->has(WordPressConfig::class)) {
            $wpConfig = $app->make(WordPressConfig::class);
            if (is_array($wpConfig)) {
                $mysqlDb = isset($wpConfig['DB_NAME']) && is_string($wpConfig['DB_NAME']) ? $wpConfig['DB_NAME'] : null;
                $mysqlHost = isset($wpConfig['DB_HOST']) && is_string($wpConfig['DB_HOST']) ? $wpConfig['DB_HOST'] : '127.0.0.1';
                $mysqlUser = isset($wpConfig['DB_USER']) && is_string($wpConfig['DB_USER']) ? $wpConfig['DB_USER'] : 'root';
                $mysqlPass = isset($wpConfig['DB_PASSWORD']) && is_string($wpConfig['DB_PASSWORD']) ? $wpConfig['DB_PASSWORD'] : '';
            }
        }

        if ($mysqlDb === null) {
            $mysqlDb = self::env('DB_MYSQL_DATABASE') ?: self::env('DB_NAME') ?: null;
            $mysqlHost = self::env('DB_MYSQL_HOST') ?: self::env('DB_HOST', '127.0.0.1');
            $mysqlPort = (int) (self::env('DB_MYSQL_PORT') ?: self::env('DB_PORT', '3306'));
            $mysqlUser = self::env('DB_MYSQL_USERNAME') ?: self::env('DB_USER', 'root');
            $mysqlPass = self::env('DB_MYSQL_PASSWORD') ?: self::env('DB_PASSWORD', '');
        }

        if (is_string($mysqlDb) && $mysqlDb !== '') {
            $drivers['wordpress_mysql'] = new Config\MySQLDriverConfig(
                connection: new Config\MySQL\TcpConnectionConfig(
                    database: $mysqlDb,
                    host: $mysqlHost,
                    port: $mysqlPort,
                    user: $mysqlUser !== '' ? $mysqlUser : null,
                    password: $mysqlPass !== '' ? $mysqlPass : null,
                ),
            );
            $databases['wordpress'] = ['connection' => 'wordpress_mysql'];
            return true;
        }

        return false;
    }

    private static function env(string $key, string $default = ''): string
    {
        $value = getenv($key);
        return $value !== false && $value !== '' ? $value : $default;
    }
}
