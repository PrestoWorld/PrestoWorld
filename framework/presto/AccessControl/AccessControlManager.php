<?php

declare(strict_types=1);

namespace PrestoWorld\AccessControl;

use PrestoWorld\AccessControl\Contracts\AccessControlInterface;
use PrestoWorld\AccessControl\Contracts\AccessControlRepositoryInterface;
use PrestoWorld\AccessControl\Contracts\Strategy\AccessControlStrategyInterface;
use PrestoWorld\AccessControl\Exception\InvalidModelException;
use PrestoWorld\AccessControl\ModulePermissionManager;
use PrestoWorld\AccessControl\Rbac\RbacStrategy;
use PrestoWorld\AccessControl\Repository\DatabaseAccessControlRepository;
use Witals\Framework\Application;

/**
 * AccessControlManager — Context trong pattern Strategy.
 *
 * Giữ một AccessControlStrategyInterface (mô hình phân quyền hiện hành)
 * và delegate mọi yêu cầu can/crash về nó. Có thể hot-swap giữa các mô hình
 * RBAC, ABAC, ACL, PBAC, DAC bằng cách gọi setStrategy() hoặc
 * switchModel().
 *
 * Các mô hình khác (ABAC, ACL, PBAC, DAC) có thể được cung cấp bởi plugin
 * hoặc module, đăng ký vào container dưới tên 'access.control.strategy.<model>'.
 */
class AccessControlManager implements AccessControlInterface
{
    /** @var array<string, class-string<AccessControlStrategyInterface>> */
    protected array $availableModels = [
        'rbac' => RbacStrategy::class,
    ];

    /** @var array<string, AccessControlStrategyInterface> */
    protected array $instances = [];

    protected ?AccessControlStrategyInterface $current = null;

    protected string $currentModel = 'rbac';

    public function __construct(
        protected Application $app,
    ) {}

    /**
     * Đăng ký một mô hình phân quyền mới (gọi từ plugin/module).
     *
     * @param class-string<AccessControlStrategyInterface> $strategyClass
     */
    public function registerModel(string $model, string $strategyClass): void
    {
        $this->availableModels[$model] = $strategyClass;
    }

    /**
     * Lấy (hoặc tạo lazy) strategy instance cho một mô hình.
     */
    public function getModel(string $model): AccessControlStrategyInterface
    {
        if (isset($this->instances[$model])) {
            return $this->instances[$model];
        }

        $class = $this->availableModels[$model] ?? null;
        if ($class === null || !class_exists($class)) {
            throw InvalidModelException::unsupported($model);
        }

        $instance = new $class($this->app);

        // Inject repository nếu có trong container
        if ($this->app->has(AccessControlRepositoryInterface::class)) {
            /** @var AccessControlRepositoryInterface $repository */
            $repository = $this->app->make(AccessControlRepositoryInterface::class);
            $instance->setRepository($repository);
        }

        $instance->initialize();
        $this->instances[$model] = $instance;

        return $instance;
    }

    /**
     * Chuyển sang mô hình phân quyền khác (hot-swap).
     */
    public function switchModel(string $model): void
    {
        $this->current = $this->getModel($model);
        $this->currentModel = $model;
    }

    public function currentModel(): string
    {
        return $this->currentModel;
    }

    public function currentStrategy(): ?AccessControlStrategyInterface
    {
        return $this->current;
    }

    /**
     * Đảm bảo có strategy hiện hành (mặc định RBAC).
     */
    protected function ensureCurrent(): AccessControlStrategyInterface
    {
        if ($this->current === null) {
            $this->switchModel($this->currentModel);
        }

        /** @var AccessControlStrategyInterface $current */
        $current = $this->current;

        return $current;
    }

    // ── AccessControlInterface delegation ─────────────────────────

    public function can(string $subject, string $action, mixed $resource = null, array $context = []): array
    {
        return $this->ensureCurrent()->can($subject, $action, $resource, $context);
    }

    /**
     * @param array<string, mixed> $context
     * @return array<string, bool>
     */
    public function actions(string $subject, mixed $resource = null, array $context = []): array
    {
        return $this->ensureCurrent()->actions($subject, $resource, $context);
    }

    public function model(): string
    {
        return $this->currentModel;
    }

    public function version(): string
    {
        return $this->ensureCurrent()->version();
    }

    // ── RoleManager delegation ────────────────────────────────────

    /**
     * @param array<string> $permissions
     */
    public function createRole(string $slug, string $name, array $permissions = [], string $description = ''): void
    {
        $this->ensureCurrent()->createRole($slug, $name, $permissions, $description);
    }

    public function deleteRole(string $slug): void
    {
        $this->ensureCurrent()->deleteRole($slug);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getRole(string $slug): ?array
    {
        return $this->ensureCurrent()->getRole($slug);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listRoles(): array
    {
        return $this->ensureCurrent()->listRoles();
    }

    public function grantPermission(string $roleSlug, string $permission): void
    {
        $this->ensureCurrent()->grantPermission($roleSlug, $permission);
    }

    public function revokePermission(string $roleSlug, string $permission): void
    {
        $this->ensureCurrent()->revokePermission($roleSlug, $permission);
    }

    public function assignRole(string $subject, string $roleSlug): void
    {
        $this->ensureCurrent()->assignRole($subject, $roleSlug);
    }

    public function unassignRole(string $subject, string $roleSlug): void
    {
        $this->ensureCurrent()->unassignRole($subject, $roleSlug);
    }

    /** @return array<int, string> */
    public function getSubjectRoles(string $subject): array
    {
        return $this->ensureCurrent()->getSubjectRoles($subject);
    }

    public function hasRole(string $subject, string $roleSlug): bool
    {
        return $this->ensureCurrent()->hasRole($subject, $roleSlug);
    }

    // ── PermissionManager delegation ──────────────────────────────

    public function createPermission(string $slug, string $name, string $description = '', string $resource = '*'): void
    {
        $this->ensureCurrent()->createPermission($slug, $name, $description, $resource);
    }

    public function deletePermission(string $slug): void
    {
        $this->ensureCurrent()->deletePermission($slug);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getPermission(string $slug): ?array
    {
        return $this->ensureCurrent()->getPermission($slug);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listPermissions(): array
    {
        return $this->ensureCurrent()->listPermissions();
    }

    /** @return array<string, class-string<AccessControlStrategyInterface>> */
    public function getAvailableModels(): array
    {
        return $this->availableModels;
    }

    // ── ModulePermissionManager delegation ──────────────────────────

    /**
     * Lấy ModulePermissionManager instance (đăng ký module, role, group, permission).
     */
    public function modulePermissions(): ModulePermissionManager
    {
        if ($this->app->has(ModulePermissionManager::class)) {
            return $this->app->make(ModulePermissionManager::class);
        }

        return ModulePermissionManager::make($this->app);
    }

    /**
     * Đăng ký một module vào hệ thống phân quyền.
     *
     * @param array<string, mixed> $options
     */
    public function registerModule(string $module, string $label, array $options = []): void
    {
        $this->modulePermissions()->registerModule($module, $label, $options);
    }

    /**
     * Kiểm tra subject có quyền action (module:feature) trên module không.
     */
    public function canModule(string $subject, string $module, string $feature, mixed $resource = null, array $context = []): array
    {
        return $this->modulePermissions()->canModule($subject, $module, $feature, $resource, $context);
    }

    /**
     * Tạo data resource.
     *
     * @param array<string, mixed> $resource
     */
    public function createDataResource(array $resource): void
    {
        $this->ensureCurrent()->createDataResource($resource);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listDataResources(string $module = ''): array
    {
        $strategy = $this->ensureCurrent();
        $all = $strategy->listDataResources();
        if ($module === '') {
            return $all;
        }

        return array_values(array_filter($all, fn($r) => ($r['module'] ?? null) === $module));
    }

    /**
     * Tạo data feature.
     *
     * @param array<string, mixed> $feature
     */
    public function createDataFeature(array $feature): void
    {
        $this->ensureCurrent()->createDataFeature($feature);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listDataFeatures(string $resourceSlug = ''): array
    {
        return $this->ensureCurrent()->listDataFeatures($resourceSlug);
    }

    /**
     * Tạo module permission.
     *
     * @param array<string, mixed> $permission
     */
    public function createModulePermission(array $permission): void
    {
        $this->ensureCurrent()->createModulePermission($permission);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listModulePermissions(string $module = ''): array
    {
        return $this->ensureCurrent()->listModulePermissions($module);
    }

    /**
     * Tạo user group.
     *
     * @param array<string, mixed> $group
     */
    public function createUserGroup(array $group): void
    {
        $this->ensureCurrent()->createUserGroup($group);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getUserGroup(string $slug): ?array
    {
        return $this->ensureCurrent()->getUserGroup($slug);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listUserGroups(string $module = ''): array
    {
        return $this->ensureCurrent()->listUserGroups($module);
    }

    /**
     * Gán role cho group.
     */
    public function grantGroupRole(string $groupSlug, string $roleSlug): void
    {
        $this->ensureCurrent()->grantGroupRole($groupSlug, $roleSlug);
    }

    /**
     * Gán subject vào group.
     */
    public function assignSubjectToGroup(string $subject, string $groupSlug): void
    {
        $this->ensureCurrent()->assignSubjectToGroup($subject, $groupSlug);
    }

    /**
     * @return array<int, string>
     */
    public function getSubjectGroups(string $subject): array
    {
        return $this->ensureCurrent()->getSubjectGroups($subject);
    }

    public function hasSubjectInGroup(string $subject, string $groupSlug): bool
    {
        return $this->ensureCurrent()->hasSubjectInGroup($subject, $groupSlug);
    }

    /**
     * Đăng ký role cho module.
     */
    public function registerModuleRole(string $module, string $roleSlug): void
    {
        $this->ensureCurrent()->registerModuleRole($module, $roleSlug);
    }

    /**
     * Đăng ký feature cho module.
     */
    public function registerModuleFeature(string $module, string $featureSlug): void
    {
        $this->ensureCurrent()->registerModuleFeature($module, $featureSlug);
    }

    /**
     * @return array<int, string>
     */
    public function getModuleRoles(string $module): array
    {
        return $this->ensureCurrent()->getModuleRoles($module);
    }

    /**
     * @return array<int, string>
     */
    public function getModuleFeatures(string $module): array
    {
        return $this->ensureCurrent()->getModuleFeatures($module);
    }

    /**
     * Tạo helper shortcut: auth()->can(...) hoặc access()->can(...)
     */
    public static function make(Application $app): self
    {
        return new self($app);
    }
}
