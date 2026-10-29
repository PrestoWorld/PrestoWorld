<?php

declare(strict_types=1);

namespace PrestoWorld\AccessControl;

use Cycle\Database\DatabaseInterface;

final class FirstUserRootAssigner
{
    public function __construct(private readonly DatabaseInterface $db)
    {
    }

    public function assign(string $subject): void
    {
        $tables = [
            'acl_roles',
            'acl_permissions',
            'acl_role_permissions',
            'acl_subject_roles',
        ];
        foreach ($tables as $table) {
            if (!$this->db->hasTable($table)) {
                throw new \RuntimeException("Cannot assign the root role: table {$table} is missing.");
            }
        }

        $now = (new \DateTimeImmutable())->format('Y-m-d H:i:s');
        $rootRole = $this->db->select('slug')
            ->from('acl_roles')
            ->where('slug', 'root')
            ->run()
            ->fetch();
        if (!$rootRole) {
            $this->db->insert('acl_roles')->values([
                'slug' => 'root',
                'name' => 'Root',
                'description' => 'Toàn quyền quản trị hệ thống và truy cập mọi BookPress site',
                'module' => null,
                'is_system' => true,
                'created_at' => $now,
            ])->run();
        }

        $wildcardPermission = $this->db->select('slug')
            ->from('acl_permissions')
            ->where('slug', '*')
            ->run()
            ->fetch();
        if (!$wildcardPermission) {
            $this->db->insert('acl_permissions')->values([
                'slug' => '*',
                'name' => 'Toàn quyền',
                'description' => 'Toàn quyền hệ thống',
                'resource' => '*',
                'module' => null,
                'created_at' => $now,
            ])->run();
        }

        $rootPermission = $this->db->select('role')
            ->from('acl_role_permissions')
            ->where('role', 'root')
            ->where('permission', '*')
            ->run()
            ->fetch();
        if (!$rootPermission) {
            $this->db->insert('acl_role_permissions')->values([
                'role' => 'root',
                'permission' => '*',
                'created_at' => $now,
            ])->run();
        }

        $rootAssignment = $this->db->select('role')
            ->from('acl_subject_roles')
            ->where('subject', $subject)
            ->where('role', 'root')
            ->run()
            ->fetch();
        if (!$rootAssignment) {
            $this->db->insert('acl_subject_roles')->values([
                'subject' => $subject,
                'role' => 'root',
                'created_at' => $now,
            ])->run();
        }
    }
}
