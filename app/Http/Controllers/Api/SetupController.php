<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use Witals\Framework\Application;
use Witals\Framework\Http\Request;
use Witals\Framework\Http\Response;
use Psr\Log\LoggerInterface;
use Cycle\Database\DatabaseInterface;
use Cycle\Database\Exception\Exception as DatabaseException;
use PrestoWorld\Database\SchemaMigrationManager;
use App\Foundation\Database\ModuleSchemaManager;
use PDO;

/**
 * Handle installation steps before authentication.
 */
class SetupController
{
    protected Application $app;
    protected LoggerInterface $logger;

    public function __construct(Application $app, LoggerInterface $logger)
    {
        $this->app = $app;
        $this->logger = $logger;
    }

    /**
     * Check if PrestoWorld is already installed.
     */
    public function checkInstalled(Request $request): Response
    {
        $installed = $this->isInstalled();
        return Response::json(['installed' => $installed]);
    }

    /**
     * Get environment information.
     */
    public function env(Request $request): Response
    {
        $envInfo = [
            'php_version' => phpversion(),
            'php_sapi' => PHP_SAPI,
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? '',
            'max_execution_time' => ini_get('max_execution_time'),
            'memory_limit' => ini_get('memory_limit'),
            'post_max_size' => ini_get('post_max_size'),
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'timezone' => date_default_timezone_get(),
            'extensions' => get_loaded_extensions(),
            'db_drivers' => [], // Will populate based on available drivers
        ];

        // Check for PDO drivers
        if (class_exists('PDO')) {
            $drivers = PDO::getAvailableDrivers();
            $envInfo['db_drivers'] = $drivers;
            $envInfo['pdo_available'] = true;
        } else {
            $envInfo['pdo_available'] = false;
        }

        // Check for specific extensions we care about
        $envInfo['has_pdo_pgsql'] = in_array('pgsql', $envInfo['db_drivers'] ?? []);
        $envInfo['has_pdo_mysql'] = in_array('mysql', $envInfo['db_drivers'] ?? []);
        $envInfo['has_pdo_sqlite'] = in_array('sqlite', $envInfo['db_drivers'] ?? []);
        $envInfo['has_openssl'] = extension_loaded('openssl');
        $envInfo['has_curl'] = extension_loaded('curl');
        $envInfo['has_gd'] = extension_loaded('gd');
        $envInfo['has_xml'] = extension_loaded('xml');
        $envInfo['has_mbstring'] = extension_loaded('mbstring');
        $envInfo['has_json'] = extension_loaded('json');

        return Response::json($envInfo);
    }

    /**
     * Test database connection.
     */
    public function testDb(Request $request): Response
    {
        $input = $this->extractInput($request);

        if (!$input) {
            return Response::json(['error' => 'Invalid JSON payload'], 400);
        }

        // Validate required fields (password may be empty string for local environments)
        $required = ['connection', 'host', 'port', 'name', 'username'];
        foreach ($required as $field) {
            if (!isset($input[$field]) || $input[$field] === '') {
                return Response::json(['error' => "Missing required field: $field"], 400);
            }
        }
        $input['password'] = (string) ($input['password'] ?? '');

        try {
            // Build Cycle ORM v2 typed driver config objects
            switch ($input['connection']) {
                case 'pgsql':
                    $connection = new \Cycle\Database\Config\Postgres\TcpConnectionConfig(
                        database: $input['name'],
                        host: $input['host'],
                        port: (int) $input['port'],
                        user: $input['username'],
                        password: $input['password'],
                    );
                    $driverConfig = new \Cycle\Database\Config\PostgresDriverConfig(
                        connection: $connection,
                    );
                    break;

                case 'mysql':
                    $connection = new \Cycle\Database\Config\MySQL\TcpConnectionConfig(
                        database: $input['name'],
                        host: $input['host'],
                        port: (int) $input['port'],
                        charset: 'utf8mb4',
                        user: $input['username'],
                        password: $input['password'],
                    );
                    $driverConfig = new \Cycle\Database\Config\MySQLDriverConfig(
                        connection: $connection,
                    );
                    break;

                case 'sqlite':
                    $connection = new \Cycle\Database\Config\SQLite\FileConnectionConfig(
                        database: $input['name'],
                    );
                    $driverConfig = new \Cycle\Database\Config\SQLiteDriverConfig(
                        connection: $connection,
                    );
                    break;

                default:
                    return Response::json(['error' => 'Unsupported database connection type'], 400);
            }

            // Build DatabaseConfig with typed driver config objects (Cycle ORM v2 API)
            $databaseConfig = new \Cycle\Database\Config\DatabaseConfig([
                'default' => 'default',
                'databases' => [
                    'default' => ['connection' => $input['connection']],
                ],
                'connections' => [
                    $input['connection'] => $driverConfig,
                ],
            ]);

            $manager = new \Cycle\Database\DatabaseManager($databaseConfig);
            $db = $manager->database();

            // Try to connect and list tables to verify the connection works
            $tables = $db->getTables();

            return Response::json(['success' => true, 'message' => 'Connection successful', 'tables_count' => count($tables)]);
        } catch (\Throwable $e) {
            $this->logger->error('Database connection test failed', ['error' => $e->getMessage()]);
            return Response::json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get available themes.
     */
    public function themes(Request $request): Response
    {
        $themesDir = $this->app->basePath() . '/content/themes';
        $themes = [];

        if (is_dir($themesDir)) {
            $items = scandir($themesDir);
            foreach ($items as $item) {
                if ($item === '.' || $item === '..') continue;
                $themePath = $themesDir . '/' . $item;
                if (is_dir($themePath)) {
                    // Try to read theme metadata (if exists)
                    $name = ucfirst($item); // Default to directory name
                    $description = 'A PrestoWorld theme';
                    $screenshot = null;

                    // Look for theme.json or similar
                    $metaFile = $themePath . '/theme.json';
                    if (file_exists($metaFile)) {
                        try {
                            $meta = json_decode(file_get_contents($metaFile), true);
                            if (is_array($meta)) {
                                $name = $meta['name'] ?? $name;
                                $description = $meta['description'] ?? $description;
                                $screenshot = $meta['screenshot'] ?? null;
                            }
                        } catch (\Throwable $e) {
                            // Ignore metadata errors
                        }
                    }

                    // Look for screenshot image
                    $screenshotPaths = [
                        $themePath . '/screenshot.png',
                        $themePath . '/screenshot.jpg',
                        $themePath . '/assets/screenshot.png',
                    ];
                    foreach ($screenshotPaths as $screenshotPath) {
                        if (file_exists($screenshotPath)) {
                            $screenshot = '/content/themes/' . $item . '/' . basename($screenshotPath);
                            break;
                        }
                    }

                    $themes[] = [
                        'id' => $item,
                        'name' => $name,
                        'description' => $description,
                        'screenshot' => $screenshot
                    ];
                }
            }
        }

        return Response::json($themes);
    }

    /**
     * Get available modules.
     */
    public function modules(Request $request): Response
    {
        $modulesDir = $this->app->basePath() . '/modules';
        $modules = [];

        if (is_dir($modulesDir)) {
            $items = scandir($modulesDir);
            foreach ($items as $item) {
                if ($item === '.' || $item === '..') continue;
                $modulePath = $modulesDir . '/' . $item;
                if (is_dir($modulePath)) {
                    // Try to read manifest.json
                    $name = ucfirst($item);
                    $description = 'A PrestoWorld module';
                    $manifestFile = $modulePath . '/manifest.json';
                    if (file_exists($manifestFile)) {
                        try {
                            $manifest = json_decode(file_get_contents($manifestFile), true);
                            if (is_array($manifest)) {
                                $name = $manifest['name'] ?? $name;
                                $description = $manifest['description'] ?? $description;
                            }
                        } catch (\Throwable $e) {
                            // Ignore manifest errors
                        }
                    }

                    $modules[] = [
                        'id' => $item,
                        'name' => $name,
                        'description' => $description
                    ];
                }
            }
        }

        return Response::json($modules);
    }

    /**
     * Install PrestoWorld with provided configuration.
     */
    public function install(Request $request): Response
    {
        // Check if already installed
        if ($this->isInstalled()) {
            return Response::json(['error' => 'Application is already installed.'], 409);
        }

        $input = $this->extractInput($request);

        if (!$input) {
            return Response::json(['error' => 'Invalid JSON payload'], 400);
        }

        // Validate required fields (db_password may be empty string for local environments)
        $required = ['db_connection', 'db_host', 'db_port', 'db_name', 'db_username', 'site_title', 'admin_email', 'admin_username', 'admin_password'];
        foreach ($required as $field) {
            if (!isset($input[$field]) || $input[$field] === '') {
                return Response::json(['error' => "Missing required field: $field"], 400);
            }
        }
        $input['db_password'] = (string) ($input['db_password'] ?? '');

        // Update .env file with database connection
        $this->updateEnvFile($input);

        // Initialize application to get services
        $this->app->boot();

        /** @var DatabaseInterface $db */
        $db = $this->app->make(DatabaseInterface::class);

        // Test database connection
        try {
            // Ensure we can connect
            $db->getSchemaManager()->listTables();
        } catch (\Throwable $e) {
            $this->logger->error('Database connection test failed', ['error' => $e->getMessage()]);
            return Response::json(['error' => 'Database connection failed: ' . $e->getMessage()], 500);
        }

        // Run migrations (core, modules, framework)
        $manager = new SchemaMigrationManager($db, $this->app->basePath());
        $migrationResult = $manager->runMigrations(false);
        $this->logger->info('Migrations run', $migrationResult);

        // Sync module schemas (create tables from module definitions)
        if ($this->app->has(\App\Foundation\Module\ModuleManager::class)) {
            $moduleManager = $this->app->make(\App\Foundation\Module\ModuleManager::class);
            $schemaManager = $this->app->make(\App\Foundation\Database\ModuleSchemaManager::class);

            foreach ($moduleManager->allSorted() as $module) {
                if ($module->isEnabled()) {
                    $synced = $schemaManager->syncModule($module->getPath());
                    if (!empty($synced)) {
                        $this->logger->info('SchemaManager: [{module}] synced tables: {tables}', ['module' => $module->getName(), 'tables' => implode(', ', $synced)]);
                    }
                }
            }
        }

        // Create admin user if not exists
        $tablePrefix = getenv('PW_TABLE_PREFIX') ?: 'pw_';
        $usersTable = $tablePrefix . 'users';
        if ($db->hasTable($usersTable)) {
            // Check if admin user already exists (by email or username)
            $stmt = $db->prepare("SELECT id FROM {$usersTable} WHERE email = ? OR username = ?");
            $stmt->execute([$input['admin_email'], $input['admin_username']]);
            if (!$stmt->fetch()) {
                // Insert admin user
                $hashedPassword = password_hash($input['admin_password'], PASSWORD_DEFAULT);
                $stmt = $db->prepare("INSERT INTO {$usersTable} (email, username, password_hash, display_name, role, created_at, updated_at) VALUES (?, ?, ?, ?, ?, NOW(), NOW())");
                $stmt->execute([$input['admin_email'], $input['admin_username'], $hashedPassword, $input['admin_username'] ?? 'Administrator', 'administrator']);
                $this->logger->info('Admin user created', ['email' => $input['admin_email']]);
            }
        }

        // Set installed flag
        $optionsTable = $tablePrefix . 'options';
        if ($db->hasTable($optionsTable)) {
            $db->insert($optionsTable)->values([
                'option_name' => 'presto_installed',
                'option_value' => '1',
                'autoload' => 'yes',
            ])->onDuplicateKeyUpdate([
                'option_value' => '1',
            ])->run();
        }

        return Response::json(['success' => true, 'message' => 'Installation completed']);
    }

    /**
     * Check if PrestoWorld is already installed.
     */
    private function isInstalled(): bool
    {
        try {
            $dbal = $this->app->make(\Cycle\Database\DatabaseProviderInterface::class);
            $db = $dbal->database();
            $tablePrefix = getenv('PW_TABLE_PREFIX') ?: 'pw_';
            $optionsTable = $tablePrefix . 'options';

            if (!$db->hasTable($optionsTable)) {
                return false;
            }

            $stmt = $db->prepare("SELECT option_value FROM {$optionsTable} WHERE option_name = ?");
            $stmt->execute(['presto_installed']);
            $row = $stmt->fetch();

            return $row && $row['option_value'] === '1';
        } catch (\Throwable $e) {
            // If we cannot determine, assume not installed to allow installation attempt
            return false;
        }
    }

    /**
     * Update .env file with database connection settings.
     */
    private function updateEnvFile(array $input): void
    {
        $basePath = $this->app->basePath();
        $envPath = $basePath . '/.env';

        $lines = [];
        if (file_exists($envPath)) {
            $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        }

        $updated = [];
        $found = [];

        foreach ($lines as $line) {
            if (trim($line) === '' || str_starts_with(trim($line), '#')) {
                $updated[] = $line;
                continue;
            }

            if (preg_match('/^([^=]+)=(.*)$/', $line, $matches)) {
                $key = trim($matches[1]);
                $value = trim($matches[2]);

                switch ($key) {
                    case 'DB_CONNECTION':
                        $updated[] = 'DB_CONNECTION=' . $input['db_connection'];
                        $found[] = $key;
                        break;
                    case 'DB_PGSQL_HOST':
                    case 'DB_MYSQL_HOST':
                        $updated[] = 'DB_PGSQL_HOST=' . $input['db_host'];
                        $updated[] = 'DB_MYSQL_HOST=' . $input['db_host'];
                        $found[] = 'DB_PGSQL_HOST';
                        $found[] = 'DB_MYSQL_HOST';
                        break;
                    case 'DB_PGSQL_PORT':
                    case 'DB_MYSQL_PORT':
                        $updated[] = 'DB_PGSQL_PORT=' . $input['db_port'];
                        $updated[] = 'DB_MYSQL_PORT=' . $input['db_port'];
                        $found[] = 'DB_PGSQL_PORT';
                        $found[] = 'DB_MYSQL_PORT';
                        break;
                    case 'DB_PGSQL_DATABASE':
                    case 'DB_MYSQL_DATABASE':
                        $updated[] = 'DB_PGSQL_DATABASE=' . $input['db_name'];
                        $updated[] = 'DB_MYSQL_DATABASE=' . $input['db_name'];
                        $found[] = 'DB_PGSQL_DATABASE';
                        $found[] = 'DB_MYSQL_DATABASE';
                        break;
                    case 'DB_PGSQL_USERNAME':
                    case 'DB_MYSQL_USERNAME':
                        $updated[] = 'DB_PGSQL_USERNAME=' . $input['db_username'];
                        $updated[] = 'DB_MYSQL_USERNAME=' . $input['db_username'];
                        $found[] = 'DB_PGSQL_USERNAME';
                        $found[] = 'DB_MYSQL_USERNAME';
                        break;
                    case 'DB_PGSQL_PASSWORD':
                    case 'DB_MYSQL_PASSWORD':
                        $updated[] = 'DB_PGSQL_PASSWORD=' . $input['db_password'];
                        $updated[] = 'DB_MYSQL_PASSWORD=' . $input['db_password'];
                        $found[] = 'DB_PGSQL_PASSWORD';
                        $found[] = 'DB_MYSQL_PASSWORD';
                        break;
                    default:
                        $updated[] = $line;
                }
            } else {
                $updated[] = $line;
            }
        }

        // Add missing keys
        $defaults = [
            'DB_CONNECTION' => $input['db_connection'],
            'DB_PGSQL_HOST' => $input['db_host'],
            'DB_MYSQL_HOST' => $input['db_host'],
            'DB_PGSQL_PORT' => $input['db_port'],
            'DB_MYSQL_PORT' => $input['db_port'],
            'DB_PGSQL_DATABASE' => $input['db_name'],
            'DB_MYSQL_DATABASE' => $input['db_name'],
            'DB_PGSQL_USERNAME' => $input['db_username'],
            'DB_MYSQL_USERNAME' => $input['db_username'],
            'DB_PGSQL_PASSWORD' => $input['db_password'],
            'DB_MYSQL_PASSWORD' => $input['db_password'],
        ];

        foreach ($defaults as $key => $value) {
            if (!in_array($key, $found, true)) {
                $updated[] = $key . '=' . $value;
            }
        }

        // Ensure PW_TABLE_PREFIX is set if provided
        if (!empty($input['db_prefix'])) {
            $prefixKey = 'PW_TABLE_PREFIX';
            $foundPrefix = false;
            foreach ($updated as $i => $line) {
                if (str_starts_with($line, $prefixKey . '=')) {
                    $updated[$i] = $prefixKey . '=' . $input['db_prefix'];
                    $foundPrefix = true;
                    break;
                }
            }
            if (!$foundPrefix) {
                $updated[] = $prefixKey . '=' . $input['db_prefix'];
            }
        }

        file_put_contents($envPath, implode("\n", $updated) . "\n");
    }

    /**
     * Safely extract input data as array from Request.
     */
    protected function extractInput(Request $request): ?array
    {
        if (method_exists($request, 'getJson')) {
            $json = $request->getJson();
            if ($json !== null) {
                return $json;
            }
        }

        $body = $request->body();
        if ($body !== null && $body !== '') {
            $decoded = json_decode($body, true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        $post = $request->post();
        if (!empty($post) && is_array($post)) {
            return $post;
        }

        return null;
    }
}
