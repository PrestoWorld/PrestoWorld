<?php

declare(strict_types=1);

namespace PrestoWorld\AccessControl;

use PrestoWorld\AccessControl\Contracts\AccessControlRepositoryInterface;
use PrestoWorld\AccessControl\Repository\DatabaseAccessControlRepository;
use Witals\Framework\Module\Module as WitalsModule;
use Cycle\Database\DatabaseInterface;

class Module extends WitalsModule
{
    public function getName(): string
    {
        return 'PrestoWorld Access Control';
    }

    public function getVersion(): string
    {
        return '1.1.0';
    }

    public function getDescription(): string
    {
        return 'Hệ thống phân quyền dựa trên Role-Based Access Control (RBAC), hỗ trợ phân quyền theo module, data resource, data feature và user group. Các module có thể đăng ký roles, permissions, groups của riêng mình.';
    }

    public function register(): void
    {
        // ── Repository ──
        $this->app->singleton(AccessControlRepositoryInterface::class, function ($app) {
            return new DatabaseAccessControlRepository(
                $app->make(DatabaseInterface::class)
            );
        });
        $this->app->alias(AccessControlRepositoryInterface::class, 'access.control.repository');

        // ── Manager (Context/Strategy) ──
        $this->app->singleton(AccessControlManager::class, function ($app) {
            return new AccessControlManager($app);
        });
        $this->app->alias(AccessControlManager::class, 'access.control');

        // ── ModulePermissionManager ──
        $this->app->singleton(ModulePermissionManager::class, function ($app) {
            return ModulePermissionManager::make($app);
        });
        $this->app->alias(ModulePermissionManager::class, 'access.control.module.permissions');

        // Đăng ký RBAC là mô hình mặc định
        /** @var AccessControlManager $manager */
        $manager = $this->app->make(AccessControlManager::class);
        $manager->registerModel('rbac', \PrestoWorld\AccessControl\Rbac\RbacStrategy::class);
    }

    public function boot(): void
    {
        /** @var AccessControlManager $manager */
        $manager = $this->app->make(AccessControlManager::class);

        // Đảm bảo RBAC (mô hình mặc định) được khởi tạo
        $manager->getModel('rbac');

        // Sync schema (schema.json) qua ModuleSchemaManager nếu có
        if ($this->app->has(\App\Foundation\Database\ModuleSchemaManager::class)) {
            /** @var \App\Foundation\Database\ModuleSchemaManager $schemaManager */
            $schemaManager = $this->app->make(\App\Foundation\Database\ModuleSchemaManager::class);
            $schemaManager->syncModule(__DIR__);
        }

        // Tạo các role mặc định nếu chưa có
        $this->seedDefaultRoles($manager);

        // Đăng ký các module mặc định vào hệ thống phân quyền
        $this->registerDefaultModules();
    }

    protected function seedDefaultRoles(AccessControlManager $manager): void
    {
        $defaultRoles = [
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
                'permissions' => [
                    'posts.read:published',
                ],
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
                $manager->createRole(
                    $slug,
                    $data['name'],
                    $data['permissions'],
                    $data['description']
                );
            }
        }
    }

    /**
     * Đăng ký các module hệ thống mặc định vào hệ thống phân quyền.
     * Các module khác có thể gọi ModulePermissionManager::registerModule()
     * từ boot() của chính mình.
     */
    protected function registerDefaultModules(): void
    {
        /** @var ModulePermissionManager $modulePerm */
        $modulePerm = $this->app->make(ModulePermissionManager::class);

        $modules = [
            'dashboard' => 'Bảng Điều Khiển',
            'orders' => 'Đơn Hàng',
            'products' => 'Sản Phẩm',
            'customers' => 'Khách Hàng',
            'inventory' => 'Kho Hàng',
            'posts' => 'Bài Viết',
            'pages' => 'Trang',
            'media' => 'Media',
            'ecommerce' => 'TMĐT',
            'accounting' => 'Kế Toán',
        ];

        foreach ($modules as $module => $label) {
            if (!$modulePerm->isModuleRegistered($module)) {
                $modulePerm->registerModule($module, $label, [
                    'auto_roles' => true,
                ]);
            }
        }
    }
}
