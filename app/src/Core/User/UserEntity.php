<?php

declare(strict_types=1);

namespace PrestoWorld\Core\User;

/**
 * UserEntity — WP_User-like (spec 10 §10.5).
 */
class UserEntity
{
    public function __construct(
        public int $id = 0,
        public string $userLogin = '',
        public string $userPass = '',
        public string $userNicename = '',
        public string $userEmail = '',
        public string $displayName = '',
        /** @var list<string> */
        public array $roles = [],
        public string $userStatus = '0',
        public string $userRegistered = '',
    ) {
    }

    public function hasRole(string $role): bool
    {
        return in_array($role, $this->roles, true);
    }

    public function can(string $capability): bool
    {
        return \PrestoWorld\Core\CapabilityService::userCan($this->id, $capability);
    }

    /** @return array<string, mixed> */
    public function to_array(): array
    {
        return [
            'ID' => $this->id,
            'user_login' => $this->userLogin,
            'user_nicename' => $this->userNicename,
            'user_email' => $this->userEmail,
            'display_name' => $this->displayName,
            'roles' => $this->roles,
        ];
    }
}