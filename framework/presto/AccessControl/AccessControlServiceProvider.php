<?php

declare(strict_types=1);

namespace PrestoWorld\AccessControl;

use Cycle\Database\DatabaseInterface;
use PrestoWorld\AccessControl\Contracts\AccessControlRepositoryInterface;
use PrestoWorld\AccessControl\Repository\DatabaseAccessControlRepository;
use Witals\Framework\Support\ServiceProvider;

final class AccessControlServiceProvider extends ServiceProvider
{
    private bool $initialized = false;

    public function register(): void
    {
        $this->app->singleton(AccessControlRepositoryInterface::class, function ($app) {
            $prefix = getenv('PW_TABLE_PREFIX') ?: 'pw_';
            $tables = [];
            foreach ([
                'permissions',
                'roles',
                'role_permissions',
                'subject_roles',
                'data_resources',
                'data_features',
                'module_permissions',
                'user_groups',
                'group_roles',
                'subject_groups',
                'module_roles',
                'module_features',
            ] as $table) {
                $tables[$table] = $prefix . 'acl_' . $table;
            }

            return new DatabaseAccessControlRepository(
                $app->make(DatabaseInterface::class),
                $tables,
            );
        });
        $this->app->alias(AccessControlRepositoryInterface::class, 'access.control.repository');

        $this->app->singleton(AccessControlManager::class, function ($app) {
            return new AccessControlManager($app);
        });
        $this->app->alias(AccessControlManager::class, 'access.control');

        $this->app->singleton(ModulePermissionManager::class, function ($app) {
            return ModulePermissionManager::make($app);
        });
        $this->app->alias(ModulePermissionManager::class, 'access.control.module.permissions');

        $this->app->booting(function (): void {
            $this->initialize();
        });
    }

    public function boot(): void
    {
        $this->initialize();
    }

    private function initialize(): void
    {
        if ($this->initialized) {
            return;
        }

        $prefix = getenv('PW_TABLE_PREFIX') ?: 'pw_';
        $this->app->make(\App\Foundation\Database\ModuleSchemaManager::class)
            ->syncModule($this->app->basePath('framework/presto/AccessControl'), $prefix);

        $schema = json_decode(
            (string) file_get_contents(__DIR__ . '/schema.json'),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );
        $db = $this->app->make(DatabaseInterface::class);
        $missingTables = [];
        foreach ($schema['tables'] ?? [] as $table) {
            $tableName = $table['name'] ?? null;
            if (is_string($tableName)) {
                $prefixedTableName = $prefix . $tableName;
                if (!$db->hasTable($prefixedTableName)) {
                    $missingTables[] = $prefixedTableName;
                }
            }
        }
        if ($missingTables !== []) {
            throw new \RuntimeException(
                'Access Control schema is missing tables: ' . implode(', ', $missingTables),
            );
        }

        /** @var AccessControlManager $manager */
        $manager = $this->app->make(AccessControlManager::class);
        $manager->getModel('rbac');

        $this->seedDefaultRoles($manager);

        require_once __DIR__ . '/helpers.php';
        $this->initialized = true;
    }

    private function seedDefaultRoles(AccessControlManager $manager): void
    {
        $defaultRoles = [
            'root' => [
                'name' => 'Root',
                'description' => 'Toàn quyền quản trị hệ thống và truy cập mọi BookPress site',
                'permissions' => ['*'],
            ],
            'administrator' => [
                'name' => 'Quản trị viên',
                'description' => 'Có toàn quyền truy cập hệ thống',
                'permissions' => ['*'],
            ],
            'editor' => [
                'name' => 'Biên tập viên',
                'description' => 'Quản lý nội dung, bài viết, trang',
                'permissions' => [
                    'posts.read', 'posts.create', 'posts.edit', 'posts.delete',
                    'pages.read', 'pages.create', 'pages.edit', 'pages.delete',
                    'media.read', 'media.upload',
                ],
            ],
            'author' => [
                'name' => 'Tác giả',
                'description' => 'Viết và quản lý bài viết của riêng mình',
                'permissions' => [
                    'posts.read', 'posts.create', 'posts.edit:own', 'posts.delete:own',
                    'media.read', 'media.upload:own',
                ],
            ],
            'subscriber' => [
                'name' => 'Người đăng ký',
                'description' => 'Chỉ đọc nội dung công khai',
                'permissions' => ['posts.read:published'],
            ],
            'customer' => [
                'name' => 'Khách hàng',
                'description' => 'Quản lý đơn hàng, hồ sơ của riêng mình',
                'permissions' => [
                    'orders.read:own', 'orders.create:own', 'profile.read:own', 'profile.edit:own',
                ],
            ],
        ];

        foreach ($defaultRoles as $slug => $data) {
            if ($manager->getRole($slug) === null) {
                $manager->createRole($slug, $data['name'], $data['permissions'], $data['description']);
            }
        }
    }

}
