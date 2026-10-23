<?php

declare(strict_types=1);

namespace Tests\Unit\AccessControl;

use PHPUnit\Framework\TestCase;
use PrestoWorld\AccessControl\AccessControlManager;
use PrestoWorld\AccessControl\Contracts\AccessControlRepositoryInterface;
use PrestoWorld\AccessControl\Exception\PermissionNotFoundException;
use PrestoWorld\AccessControl\Exception\RoleNotFoundException;
use PrestoWorld\AccessControl\Rbac\RbacStrategy;

/**
 * In-memory repository cho unit test, không cần database.
 */
class MemoryAccessControlRepository implements AccessControlRepositoryInterface
{
    /** @var array<int, array{subject: string, role: string}> */
    public array $subjectRoles = [];
    /** @var array<int, array{role: string, permission: string}> */
    public array $rolePermissions = [];
    /** @var array<int, array{slug: string, name: string, description: string, resource: string}> */
    public array $permissions = [];
    /** @var array<int, array{slug: string, name: string, description: string}> */
    public array $roles = [];

    /** @return array<int, array{subject: string, role: string}> */
    public function loadSubjectRoles(string $subject): array
    {
        return $this->subjectRoles;
    }

    /** @return array<int, array{role: string, permission: string}> */
    public function loadRolePermissions(string $roleSlug): array
    {
        return $this->rolePermissions;
    }

    /** @return array<int, array{slug: string, name: string, description: string, resource: string}> */
    public function loadPermissions(): array
    {
        return $this->permissions;
    }

    /** @return array<int, array{slug: string, name: string, description: string}> */
    public function loadRoles(): array
    {
        return $this->roles;
    }

    public function saveSubjectRoles(string $subject, array $roles): void
    {
    }

    public function saveRolePermissions(string $roleSlug, array $permissions): void
    {
    }

    public function savePermission(array $permission): void
    {
    }

    public function saveRole(array $role): void
    {
    }

    public function deletePermission(string $slug): void
    {
    }

    public function deleteRole(string $slug): void
    {
    }
}

class RbacStrategyTest extends TestCase
{
    protected RbacStrategy $rbac;
    protected MemoryAccessControlRepository $repo;

    protected function setUp(): void
    {
        $this->repo = new MemoryAccessControlRepository();
        $this->rbac = new RbacStrategy();
        $this->rbac->setRepository($this->repo);
        $this->rbac->initialize();
    }

    public function test_can_returns_allowed_when_subject_has_role_with_permission(): void
    {
        $this->rbac->createRole('editor', 'Editor', ['posts.edit', 'posts.read']);
        $this->rbac->assignRole('user-1', 'editor');

        $result = $this->rbac->can('user-1', 'posts.edit');

        $this->assertTrue($result['allowed']);
        $this->assertSame('rbac', $result['model']);
    }

    public function test_can_returns_false_when_subject_lacks_permission(): void
    {
        $this->rbac->createRole('subscriber', 'Subscriber', ['posts.read']);
        $this->rbac->assignRole('user-2', 'subscriber');

        $result = $this->rbac->can('user-2', 'posts.delete');

        $this->assertFalse($result['allowed']);
    }

    public function test_wildcard_permission_allows_all_actions(): void
    {
        $this->rbac->createRole('administrator', 'Administrator', ['*']);
        $this->rbac->assignRole('user-3', 'administrator');

        $this->assertTrue($this->rbac->can('user-3', 'anything.at.all')['allowed']);
    }

    public function test_get_subject_roles_returns_assigned_roles(): void
    {
        $this->rbac->createRole('author', 'Author', []);
        $this->rbac->createRole('editor', 'Editor', []);
        $this->rbac->assignRole('user-4', 'author');
        $this->rbac->assignRole('user-4', 'editor');

        $this->assertSame(['author', 'editor'], $this->rbac->getSubjectRoles('user-4'));
        $this->assertTrue($this->rbac->hasRole('user-4', 'author'));
        $this->assertFalse($this->rbac->hasRole('user-4', 'administrator'));
    }

    public function test_unassign_role_removes_it(): void
    {
        $this->rbac->createRole('editor', 'Editor', []);
        $this->rbac->assignRole('user-5', 'editor');
        $this->rbac->unassignRole('user-5', 'editor');

        $this->assertSame([], $this->rbac->getSubjectRoles('user-5'));
    }

    public function test_create_role_throws_if_exists(): void
    {
        $this->rbac->createRole('editor', 'Editor', []);

        $this->expectException(RoleNotFoundException::class);
        $this->rbac->createRole('editor', 'Editor Again', []);
    }

    public function test_grant_and_revoke_permission(): void
    {
        $this->rbac->createRole('editor', 'Editor', []);
        $this->rbac->grantPermission('editor', 'posts.edit');
        $this->rbac->assignRole('user-6', 'editor');

        $this->assertTrue($this->rbac->can('user-6', 'posts.edit')['allowed']);

        $this->rbac->revokePermission('editor', 'posts.edit');
        $this->assertFalse($this->rbac->can('user-6', 'posts.edit')['allowed']);
    }

    public function test_create_permission_and_list(): void
    {
        $this->rbac->createPermission('posts.edit', 'Sửa bài viết', 'Cho phép sửa bài', 'posts');

        $perms = $this->rbac->listPermissions();
        $this->assertCount(1, $perms);
        $this->assertSame('posts.edit', $perms[0]['slug']);
    }

    public function test_delete_permission_removes_from_roles(): void
    {
        $this->rbac->createPermission('posts.edit', 'Sửa bài viết', '', 'posts');
        $this->rbac->createRole('editor', 'Editor', ['posts.edit']);
        $this->rbac->assignRole('user-7', 'editor');

        $this->assertTrue($this->rbac->can('user-7', 'posts.edit')['allowed']);

        $this->rbac->deletePermission('posts.edit');
        $this->assertFalse($this->rbac->can('user-7', 'posts.edit')['allowed']);
    }

    public function test_delete_role_removes_from_subjects(): void
    {
        $this->rbac->createRole('editor', 'Editor', ['posts.edit']);
        $this->rbac->assignRole('user-8', 'editor');

        $this->assertTrue($this->rbac->can('user-8', 'posts.edit')['allowed']);

        $this->rbac->deleteRole('editor');
        $this->assertFalse($this->rbac->can('user-8', 'posts.edit')['allowed']);
        $this->assertSame([], $this->rbac->getSubjectRoles('user-8'));
    }

    public function test_model_and_version(): void
    {
        $this->assertSame('rbac', $this->rbac->model());
        $this->assertSame('1.0.0', $this->rbac->version());
        $this->assertTrue($this->rbac->isReady());
    }

    public function test_actions_returns_map(): void
    {
        $this->rbac->createRole('editor', 'Editor', ['posts.edit', 'posts.read']);
        $this->rbac->assignRole('user-9', 'editor');

        $actions = $this->rbac->actions('user-9');
        $this->assertTrue($actions['posts.edit'] ?? false);
        $this->assertTrue($actions['posts.read'] ?? false);
    }

    public function test_unknown_subject_returns_empty(): void
    {
        $result = $this->rbac->can('nonexistent-user', 'posts.edit');
        $this->assertFalse($result['allowed']);
    }

    public function test_grant_permission_to_nonexistent_role_throws(): void
    {
        $this->expectException(RoleNotFoundException::class);
        $this->rbac->grantPermission('no-such-role', 'posts.edit');
    }
}
