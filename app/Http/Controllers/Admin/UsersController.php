<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use Cycle\Database\DatabaseInterface;
use Witals\Framework\Http\Response;

class UsersController
{
    use AdminControllerHelperTrait;

    public function __construct(
        protected DatabaseInterface $db,
    ) {
        $this->initHelpers($db);
    }

    public function users(): Response
    {
        try {
            $rows = $this->db->select('*')
                ->from($this->prefix . 'users')
                ->orderBy('user_registered', 'DESC')
                ->fetchAll();
        } catch (\Throwable) {
            $rows = [];
        }

        $users = array_map(fn(array $row) => [
            'id' => (int) ($row['ID'] ?? $row['id']),
            'username' => $row['user_login'] ?? '',
            'name' => $row['display_name'] ?? $row['user_nicename'] ?? '',
            'email' => $row['user_email'] ?? '',
            'role' => $this->resolveUserRole((int) ($row['ID'] ?? $row['id'])),
            'registered' => $this->formatDate($row['user_registered'] ?? ''),
            'posts' => 0,
        ], $rows);

        return Response::json($users);
    }

    protected function resolveUserRole(int $userId): string
    {
        try {
            $meta = $this->db->select('meta_value')
                ->from($this->prefix . 'usermeta')
                ->where('user_id', $userId)
                ->where('meta_key', 'wp_capabilities')
                ->limit(1)
                ->fetch();
            if ($meta) {
                $caps = maybe_unserialize($meta['meta_value']);
                if (is_array($caps)) {
                    $roles = array_keys($caps);
                    return $roles[0] ?? 'subscriber';
                }
            }
        } catch (\Throwable) {
        }
        return 'subscriber';
    }
}
