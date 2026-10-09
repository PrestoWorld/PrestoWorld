<?php

declare(strict_types=1);

namespace PrestoWorld\Core\User;

use PrestoWorld\Core\User\Role;

/**
 * RoleRegistry — WP_Roles replacement (spec 10 §10.5.5).
 */
class RoleRegistry
{
    /** @var array<string, Role> */
    private array $roles = [];

    public function __construct()
    {
    }

    public function getRole(string $role): ?Role
    {
        return $this->roles[$role] ?? null;
    }

    /** @return array<string, Role> */
    public function getRoles(): array
    {
        return $this->roles;
    }

    /**
     * @param list<string> $capabilities
     */
    public function addRole(string $role, string $displayName, array $capabilities = []): Role
    {
        $object = new Role($role, $displayName !== '' ? $displayName : ucfirst($role));
        foreach ($capabilities as $cap) {
            if (is_string($cap)) {
                $object->addCap($cap);
            }
        }

        $this->roles[$role] = $object;

        return $object;
    }

    public function removeRole(string $role): void
    {
        unset($this->roles[$role]);
    }

    public function roleExists(string $role): bool
    {
        return isset($this->roles[$role]);
    }

    public function addCap(string $role, string $cap, bool $grant = true): void
    {
        if (!isset($this->roles[$role])) {
            $this->roles[$role] = new Role($role, ucfirst($role));
        }

        $this->roles[$role]->addCap($cap, $grant);
    }

    public function removeCap(string $role, string $cap): void
    {
        if (isset($this->roles[$role])) {
            $this->roles[$role]->removeCap($cap);
        }
    }

    public function hasCap(string $role, string $cap): bool
    {
        return ($this->roles[$role] ?? null)?->hasCap($cap) ?? false;
    }

    public function reset(): void
    {
        $this->roles = [];
    }
}
