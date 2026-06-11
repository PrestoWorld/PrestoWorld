<?php

declare(strict_types=1);

namespace App\Providers;

use App\Support\ServiceProvider;
use Cycle\Database\Config\DatabaseConfig;
use Cycle\Database\DatabaseManager;
use Cycle\Database\DatabaseProviderInterface;
use Cycle\ORM\Factory;
use Cycle\ORM\ORM;
use Cycle\ORM\ORMInterface;
use Cycle\ORM\Schema as ORMSchema;
use Cycle\ORM\SchemaInterface;
use Cycle\Annotated;
use Cycle\Schema;
use Spiral\Tokenizer\ClassLocator;
use Symfony\Component\Finder\Finder;

class DatabaseServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->singleton(\App\Foundation\Database\QueryInterceptor::class, function ($app) {
            return new \App\Foundation\Database\QueryInterceptor($app->make(\App\Foundation\Debug\DebugBar::class));
        });

        $this->singleton(\App\Foundation\Database\ModuleSchemaManager::class, function ($app) {
            return new \App\Foundation\Database\ModuleSchemaManager(
                $app->make(\Cycle\Database\DatabaseProviderInterface::class)
            );
        });

        $this->singleton(DatabaseProviderInterface::class, function ($app) {
            $dbConfig = $app->config('database');
            $driver = $dbConfig['default'] ?? env('DB_CONNECTION', 'mysql');

            $config = new DatabaseConfig($dbConfig);
            $manager = new DatabaseManager($config);

            if (env('APP_DEBUG_BAR', false) && $app->has(\App\Foundation\Debug\DebugBar::class)) {
                $manager->setLogger($app->make(\App\Foundation\Database\QueryInterceptor::class));
            }

            return new \App\Foundation\Database\DatabaseManagerProxy($manager, $driver);
        });

        $this->singleton(\Cycle\Database\DatabaseInterface::class, function ($app) {
            return $app->make(DatabaseProviderInterface::class)->database();
        });

        $this->singleton('wpdb', function ($app) {
            return $app->make(\Cycle\Database\DatabaseInterface::class);
        });

        $this->singleton(ORMInterface::class, function ($app) {
            $dbal = $app->make(DatabaseProviderInterface::class);

            $cacheFile = $app->basePath('storage/framework/cache/orm_schema.php');
            $refresh = isset($_GET['refresh_schema']) || !file_exists($cacheFile);

            if (!$refresh) {
                $schemaArray = require $cacheFile;
            } else {
                $schemaArray = $this->getSchema($app, $dbal);

                $cacheDir = dirname($cacheFile);
                if (!is_dir($cacheDir)) {
                    mkdir($cacheDir, 0755, true);
                }

                if (is_writable($cacheDir)) {
                    $content = "<?php\n\ndeclare(strict_types=1);\n\nreturn " . var_export($schemaArray, true) . ";\n";
                    file_put_contents($cacheFile, $content);
                }
            }

            return new ORM(
                new Factory($dbal),
                new ORMSchema($schemaArray)
            );
        });

        $this->singleton(\Cycle\ORM\EntityManagerInterface::class, function ($app) {
            return new \Cycle\ORM\EntityManager($app->make(ORMInterface::class));
        });

        $app = $this->app;
        $this->app->terminating(function () use ($app) {
            if ($app->isLongRunning()) {
                $dbal = $app->make(DatabaseProviderInterface::class);
                if ($dbal instanceof \App\Foundation\Database\DatabaseManagerProxy) {
                    $dbal->disconnect();
                }
            }
        });
    }

    public function boot(): void
    {
        try {
            $app = $this->app;
            if ($app->has(\App\Foundation\Module\ModuleManager::class)) {
                $moduleManager = $app->make(\App\Foundation\Module\ModuleManager::class);
                $schemaManager = $app->make(\App\Foundation\Database\ModuleSchemaManager::class);

                foreach ($moduleManager->allSorted() as $module) {
                    if ($module->isEnabled()) {
                        $synced = $schemaManager->syncModule($module->getPath());
                        if (!empty($synced)) {
                            error_log(sprintf(
                                'SchemaManager: [%s] synced tables: %s',
                                $module->getName(),
                                implode(', ', $synced)
                            ));
                        }
                    }
                }
            }
        } catch (\Throwable $e) {
            error_log('SchemaManager boot error: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
        }
    }

    private function getSchema($app, $dbal): array
    {
        $finder = (new Finder())->files()->in([
            $app->basePath('app/Models'),
            $app->basePath('vendor/prestoworld/wp-bridge/src/Sandbox/Models'),
        ]);

        if ($app->has(\App\Foundation\Module\ModuleManager::class)) {
            $modules = $app->make(\App\Foundation\Module\ModuleManager::class)->all();
            foreach ($modules as $module) {
                if ($module->isEnabled() && is_dir($module->getPath() . '/src/Models')) {
                    $finder->in($module->getPath() . '/src/Models');
                }
            }
        }

        $classLocator = new ClassLocator($finder);

        $schema = (new Schema\Compiler())->compile(new Schema\Registry($dbal), [
            new Schema\Generator\ResetTables(),
            new Annotated\Embeddings($classLocator),
            new Annotated\Entities($classLocator),
            new Annotated\TableInheritance(),
            new Annotated\MergeColumns(),
            new Schema\Generator\GenerateRelations(),
            new Schema\Generator\GenerateTypecast(),
            new Schema\Generator\RenderTables(),
            new Schema\Generator\SyncTables(),
            new Schema\Generator\ValidateEntities(),
        ]);

        return $schema;
    }
}
