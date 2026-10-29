<?php

declare(strict_types=1);

namespace PrestoWorld\AccessControl;

use PrestoWorld\AccessControl\Contracts\AccessControlRepositoryInterface;
use PrestoWorld\AccessControl\Exception\RoleNotFoundException;
use PrestoWorld\AccessControl\Rbac\RbacStrategy;
use Witals\Framework\Application;

/**
 * ModulePermissionManager — Dịch vụ giúp các module đăng ký nhóm người dùng,
 * role, permission, data resource, data feature của riêng module đó.
 *
 * Mỗi module gọi `registerModule()` trong boot() để khai báo:
 *   - user groups (nhóm người dùng)
 *   - roles (vai trò)
 *   - permissions (quyền)
 *   - data resources (tài nguyên dữ liệu)
 *   - data features (tính năng dữ liệu)
 *   - module permissions (quyền cấp phát của module)
 *
 * Sau đó các module khác hoặc admin có thể gán role/group cho subject
 * để kiểm soát phân quyền chi tiết đối với dữ liệu và tính năng của module.
 */
class ModulePermissionManager
{
    public const PERMISSION_VIEW = 'view';
    public const PERMISSION_CREATE = 'create';
    public const PERMISSION_EDIT = 'edit';
    public const PERMISSION_DELETE = 'delete';
    public const PERMISSION_EXPORT = 'export';
    public const PERMISSION_IMPORT = 'import';
    public const PERMISSION_MANAGE = 'manage';

    protected Application $app;
    protected AccessControlManager $access;
    protected RbacStrategy $rbac;

    /** @var array<string, array<string, mixed>> Registered modules */
    protected array $registeredModules = [];

    public function __construct(Application $app, AccessControlManager $access)
    {
        $this->app = $app;
        $this->access = $access;
        $this->rbac = $access->getModel('rbac');
    }

    /**
     * Đăng ký một module vào hệ thống phân quyền. Module sẽ có:
     *   - data resource: `{module}.data`
     *   - data features: view, create, edit, delete, export, import, manage
     *   - module permissions: `{module}:{feature}`
     *   - roles: `{module}.admin`, `{module}.editor`, `{module}.viewer`
     *   - user groups: `{module}.team`
     *
     * @param array<string, mixed> $options
     *   - 'roles' => array of role definitions (slug, name, permissions, description)
     *   - 'groups' => array of group definitions (slug, name, roles, description)
     *   - 'permissions' => array of extra permission definitions
     *   - 'features' => array of extra feature slugs beyond defaults
     *   - 'auto_roles' => bool, auto-create default roles (admin/editor/viewer)
     */
    public function registerModule(string $module, string $label, array $options = []): void
    {
        if (isset($this->registeredModules[$module])) {
            return; // Đã đăng ký
        }

        $autoRoles = $options['auto_roles'] ?? true;
        $features = $options['features'] ?? [
            self::PERMISSION_VIEW,
            self::PERMISSION_CREATE,
            self::PERMISSION_EDIT,
            self::PERMISSION_DELETE,
            self::PERMISSION_EXPORT,
            self::PERMISSION_IMPORT,
            self::PERMISSION_MANAGE,
        ];

        $resourceSlug = $module . '.data';
        $resourceName = $label . ' Dữ Liệu';

        if ($this->rbac->getDataResource($resourceSlug) === null) {
            $this->rbac->createDataResource([
                'slug' => $resourceSlug,
                'name' => $resourceName,
                'description' => 'Tài nguyên dữ liệu của module ' . $module,
                'module' => $module,
            ]);
        }

        // 2. Đăng ký data features
        foreach ($features as $feature) {
            $featureSlug = $module . ':' . $feature;
            if ($this->rbac->getDataFeature($featureSlug) === null) {
                $this->rbac->createDataFeature([
                    'slug' => $featureSlug,
                    'name' => ucfirst($feature) . ' ' . $label,
                    'description' => 'Tính năng ' . $feature . ' cho ' . $label,
                    'resource' => $resourceSlug,
                    'module' => $module,
                ]);
            }
            if (!in_array($featureSlug, $this->rbac->getModuleFeatures($module), true)) {
                $this->rbac->registerModuleFeature($module, $featureSlug);
            }
        }

        // 3. Đăng ký module permissions (module:feature)
        foreach ($features as $feature) {
            $featureSlug = $module . ':' . $feature;
            $permSlug = $module . ':' . $feature;
            if ($this->rbac->getModulePermission($permSlug) === null) {
                $this->rbac->createModulePermission([
                    'slug' => $permSlug,
                    'name' => ucfirst($feature) . ' ' . $label,
                    'description' => 'Quyền ' . $feature . ' dữ liệu ' . $label,
                    'module' => $module,
                    'feature' => $featureSlug,
                    'resource' => $resourceSlug,
                ]);
            }
        }

        // 4. Extra permissions từ options
        $extraPermissions = $options['permissions'] ?? [];
        foreach ($extraPermissions as $perm) {
            $slug = $perm['slug'] ?? $module . ':' . ($perm['name'] ?? '');
            if ($this->access->getPermission($slug) === null) {
                $this->rbac->createPermission(
                    $slug,
                    $perm['name'] ?? '',
                    $perm['description'] ?? '',
                    $perm['resource'] ?? $resourceSlug
                );
            }
        }

        // 5. Auto-create default roles
        if ($autoRoles) {
            $defaultRoles = [
                $module . '.admin' => [
                    'name' => 'Quản trị ' . $label,
                    'description' => 'Toàn quyền quản lý ' . $label,
                    'permissions' => array_map(fn($f) => $module . ':' . $f, $features),
                ],
                $module . '.editor' => [
                    'name' => 'Biên tập ' . $label,
                    'description' => 'Tạo, sửa, xóa dữ liệu ' . $label,
                    'permissions' => [
                        $module . ':' . self::PERMISSION_VIEW,
                        $module . ':' . self::PERMISSION_CREATE,
                        $module . ':' . self::PERMISSION_EDIT,
                        $module . ':' . self::PERMISSION_DELETE,
                    ],
                ],
                $module . '.viewer' => [
                    'name' => 'Xem ' . $label,
                    'description' => 'Chỉ xem dữ liệu ' . $label,
                    'permissions' => [
                        $module . ':' . self::PERMISSION_VIEW,
                    ],
                ],
            ];

            foreach ($defaultRoles as $roleSlug => $roleData) {
                if ($this->access->getRole($roleSlug) === null) {
                    $this->access->createRole(
                        $roleSlug,
                        $roleData['name'],
                        $roleData['permissions'],
                        $roleData['description']
                    );
                }
                if (!in_array($roleSlug, $this->rbac->getModuleRoles($module), true)) {
                    $this->rbac->registerModuleRole($module, $roleSlug);
                }
            }
        }

        // 6. Roles tùy chỉnh từ options
        $customRoles = $options['roles'] ?? [];
        foreach ($customRoles as $role) {
            $roleSlug = $role['slug'] ?? $module . ':' . ($role['name'] ?? '');
            if ($this->access->getRole($roleSlug) === null) {
                $this->access->createRole(
                    $roleSlug,
                    $role['name'] ?? '',
                    $role['permissions'] ?? [],
                    $role['description'] ?? ''
                );
            }
            if (!in_array($roleSlug, $this->rbac->getModuleRoles($module), true)) {
                $this->rbac->registerModuleRole($module, $roleSlug);
            }
        }

        // 7. User groups từ options
        $customGroups = $options['groups'] ?? [];
        foreach ($customGroups as $group) {
            $groupSlug = $group['slug'] ?? $module . ':team';
            if ($this->rbac->getUserGroup($groupSlug) === null) {
                $this->rbac->createUserGroup([
                    'slug' => $groupSlug,
                    'name' => $group['name'] ?? $label . ' Team',
                    'description' => $group['description'] ?? 'Nhóm người dùng của module ' . $module,
                    'module' => $module,
                    'is_system' => false,
                ]);
            }

            // Gán roles cho group
            $groupRoles = $group['roles'] ?? [$module . '.viewer'];
            foreach ($groupRoles as $roleSlug) {
                if (
                    $this->access->getRole($roleSlug) !== null
                    && !in_array($roleSlug, $this->rbac->getGroupRoles($groupSlug), true)
                ) {
                    $this->rbac->grantGroupRole($groupSlug, $roleSlug);
                }
            }
        }

        $this->registeredModules[$module] = [
            'label' => $label,
            'resource' => $resourceSlug,
            'features' => $features,
            'options' => $options,
        ];
    }

    /**
     * Kiểm tra xem module đã được đăng ký chưa.
     */
    public function isModuleRegistered(string $module): bool
    {
        return isset($this->registeredModules[$module]);
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function getRegisteredModules(): array
    {
        return $this->registeredModules;
    }

    /**
     * Lấy danh sách feature slugs của module.
     *
     * @return array<int, string>
     */
    public function getModuleFeatures(string $module): array
    {
        return $this->rbac->getModuleFeatures($module);
    }

    /**
     * Lấy danh sách role slugs của module.
     *
     * @return array<int, string>
     */
    public function getModuleRoles(string $module): array
    {
        return $this->rbac->getModuleRoles($module);
    }

    /**
     * Kiểm tra subject có quyền action trên module không.
     * Action có dạng `module:feature` (ví dụ: `orders:edit`).
     */
    public function canModule(string $subject, string $module, string $feature, mixed $resource = null, array $context = []): array
    {
        $action = $module . ':' . $feature;
        return $this->rbac->can($subject, $action, $resource, $context);
    }

    /**
     * Gán subject vào group của module.
     */
    public function assignSubjectToModuleGroup(string $subject, string $module, string $groupSlug): void
    {
        $this->rbac->assignSubjectToGroup($subject, $groupSlug);
    }

    /**
     * Lấy danh sách group của module.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getModuleGroups(string $module): array
    {
        return array_values(array_filter($this->rbac->listUserGroups($module), fn($g) => ($g['module'] ?? null) === $module));
    }

    /**
     * Helper tạo AccessControlManager cho module (nếu chưa có).
     */
    public static function make(Application $app): self
    {
        $access = $app->make(AccessControlManager::class);
        return new self($app, $access);
    }
}
