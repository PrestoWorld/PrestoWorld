<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Foundation\Database\ModuleSchemaManager;
use Cycle\Database\Config\DatabaseConfig;
use Cycle\Database\Config\SQLiteDriverConfig;
use Cycle\Database\DatabaseInterface;
use Cycle\Database\DatabaseManager;
use Cycle\Database\DatabaseProviderInterface;
use PHPUnit\Framework\TestCase;
use PrestoWorld\AccessControl\AccessControlManager;
use PrestoWorld\AccessControl\AccessControlServiceProvider;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Witals\Framework\Application;
use Witals\Framework\Container\Container;

class AccessControlCoreFeatureTest extends TestCase
{
    private ?Container $previousContainer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->previousContainer = Container::getInstance();
    }

    protected function tearDown(): void
    {
        Container::setInstance($this->previousContainer);
        parent::tearDown();
    }

    public function test_core_provider_creates_schema_and_seeds_default_roles(): void
    {
        $config = new DatabaseConfig([
            'default' => 'default',
            'connections' => ['sqlite' => new SQLiteDriverConfig()],
            'databases' => ['default' => ['connection' => 'sqlite']],
        ]);
        $db = (new DatabaseManager($config))->database('default');
        $db->execute('PRAGMA foreign_keys = ON');
        $dbal = new class($db) implements DatabaseProviderInterface {
            public function __construct(private DatabaseInterface $db)
            {
            }

            public function database(?string $database = null): DatabaseInterface
            {
                return $this->db;
            }
        };

        $app = new Application(dirname(__DIR__, 2));
        $logger = new NullLogger();
        $app->instance(DatabaseProviderInterface::class, $dbal);
        $app->instance(DatabaseInterface::class, $db);
        $app->instance(LoggerInterface::class, $logger);
        $app->instance(
            ModuleSchemaManager::class,
            new ModuleSchemaManager($dbal, $logger, $db),
        );

        $provider = new AccessControlServiceProvider($app);
        $provider->register();
        $provider->boot();

        foreach ([
            'acl_permissions',
            'acl_roles',
            'acl_role_permissions',
            'acl_subject_roles',
            'acl_data_resources',
            'acl_data_features',
            'acl_module_permissions',
            'acl_user_groups',
            'acl_group_roles',
            'acl_subject_groups',
            'acl_module_roles',
            'acl_module_features',
        ] as $table) {
            $this->assertTrue($db->hasTable($table), "Expected core schema table {$table}.");
        }

        $roles = array_column($db->select('slug')->from('acl_roles')->fetchAll(), 'slug');
        $this->assertContains('administrator', $roles);
        $this->assertContains('editor', $roles);
        $this->assertSame($app->make(AccessControlManager::class), \PrestoWorld\AccessControl\access($app));

        $rolesSchema = $db->table('acl_roles')->getSchema();
        $rolesSchema->declareDropped();
        $rolesSchema->save();
        $app->make(ModuleSchemaManager::class)->syncModule(
            dirname(__DIR__, 2) . '/framework/presto/AccessControl',
        );
        $this->assertTrue($db->hasTable('acl_roles'), 'A missing table must be recreated even when the schema hash is unchanged.');

        $nextApp = new Application(dirname(__DIR__, 2));
        $nextApp->instance(DatabaseProviderInterface::class, $dbal);
        $nextApp->instance(DatabaseInterface::class, $db);
        $nextApp->instance(LoggerInterface::class, $logger);
        $nextApp->instance(
            ModuleSchemaManager::class,
            new ModuleSchemaManager($dbal, $logger, $db),
        );
        $nextProvider = new AccessControlServiceProvider($nextApp);
        $nextProvider->register();
        $nextProvider->boot();

        $this->assertSame(
            count($roles),
            count($db->select('slug')->from('acl_roles')->fetchAll()),
            'Repeated core boot must not duplicate role rows.',
        );
    }
}
