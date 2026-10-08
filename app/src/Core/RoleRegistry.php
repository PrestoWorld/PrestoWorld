<?php

declare(strict_types=1);

namespace PrestoWorld\Core;

/**
 * RoleRegistry — add_role/get_role/remove_role (spec 10 §10.4 Group users).
 *
 * instance() trả singleton object cho kiểu gọi OO (compiled output).
 */
final class RoleRegistry
{
    /** @var array<string, array<string, bool>> */
    private static array $roles = [];

    private static ?self $instance = null;

    private function __construct()
    {
    }

    public static function instance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public static function add(string $role, string $displayName = '', array $capabilities = []): void
    {
        self::$roles[$role] = [
            'name' => $displayName !== '' ? $displayName : ucfirst($role),
            'capabilities' => array_fill_keys($capabilities, true),
        ];
    }

    public static function remove(string $role): bool
    {
        $had = isset(self::$roles[$role]);
        unset(self::$roles[$role]);

        return $had;
    }

    public static function get(string $role): ?object
    {
        if (!isset(self::$roles[$role])) {
            return null;
        }

        return (object) self::$roles[$role];
    }

    public static function has(string $role): bool
    {
        return isset(self::$roles[$role]);
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function all(): array
    {
        return self::$roles;
    }

    public static function reset(): void
    {
        self::$roles = [];
        self::$instance = null;
    }
}