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
        // Register Query Interceptor
        $this->singleton(\App\Foundation\Database\QueryInterceptor::class, function ($app) {
            return new \App\Foundation\Database\QueryInterceptor($app->make(\App\Foundation\Debug\DebugBar::class));
        });

        // 1. Register Database Manager (DBAL)
        $this->singleton(DatabaseProviderInterface::class, function ($app) {
            $config = new DatabaseConfig($app->config('database'));
            $manager = new DatabaseManager($config);

            // Intercept queries for Statistics/Debug Bar
            if (env('APP_DEBUG_BAR', false) && $app->has(\App\Foundation\Debug\DebugBar::class)) {
                $manager->setLogger($app->make(\App\Foundation\Database\QueryInterceptor::class));
            }

            return $manager;
        });

        // 2. Register ORM
        $this->singleton(ORMInterface::class, function ($app) {
            $dbal = $app->make(DatabaseProviderInterface::class);
            
            $cacheFile = $app->basePath('storage/framework/cache/orm_schema.php');
            $refresh = isset($_GET['refresh_schema']) || !file_exists($cacheFile);

            if (!$refresh) {
                $schemaArray = require $cacheFile;
            } else {
                $schemaArray = $this->getSchema($app, $dbal);
                
                // Ensure cache directory exists
                $cacheDir = dirname($cacheFile);
                if (!is_dir($cacheDir)) {
                    mkdir($cacheDir, 0755, true);
                }

                // Securely save the compiled schema if directory is writable
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
    }

    private function getSchema($app, $dbal): array
    {
        // Simple schema compilation on boot (dev mode)
        // For production, this should be cached
        
        $finder = (new Finder())->files()->in([
            $app->basePath('app/Models'),
        ]);
        
        // Check if modules have models
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
            new Schema\Generator\ResetTables(),             // Re-declared table schemas (test mode)
            new Annotated\Embeddings($classLocator),        // register embeddable entities
            new Annotated\Entities($classLocator),          // register annotated entities
            new Annotated\TableInheritance(),               // register STI/JTI
            new Annotated\MergeColumns(),                   // add @Table column declarations
            new Schema\Generator\GenerateRelations(),       // generate entity relations
            new Schema\Generator\GenerateTypecast(),        // typecast non-string columns
            new Schema\Generator\RenderTables(),            // declare table schemas
            new Schema\Generator\ValidateEntities(),        // make sure all entity schemas are correct
        ]);

        return $schema;
    }
}
