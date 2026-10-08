<?php

declare(strict_types=1);

namespace PrestoWorld\Core;

/**
 * CapabilityService — current_user_can/user_can (spec 10 §10.4 Group users).
 */
final class CapabilityService
{
    /** @var array<int, list<string>> */
    private static array $userCaps = [];

    /** @var array<int, list<string>> */
    private static array $explicitDenials = [];

    private function __construct()
    {
    }

    public static function userCan(int|string $userId, string $capability, mixed ...$args): bool
    {
        $id = (int) $userId;

        if (in_array($capability, self::$explicitDenials[$id] ?? [], true)) {
            return false;
        }

        if (in_array($capability, self::$userCaps[$id] ?? [], true)) {
            return true;
        }

        $role = AuthService::role($id);
        if ($role !== null) {
            $roleCaps = RoleRegistry::get($role);
            if ($roleCaps !== null) {
                $caps = (array) ($roleCaps->capabilities ?? []);
                if (!empty($caps[$capability])) {
                    return true;
                }
            }
        }

        return $capability === 'read' || $capability === 'exist';
    }

    public static function grant(int $userId, string $capability): void
    {
        $id = (int) $userId;
        $caps = self::$userCaps[$id] ?? [];
        if (!in_array($capability, $caps, true)) {
            $caps[] = $capability;
            self::$userCaps[$id] = $caps;
        }
    }

    public static function deny(int $userId, string $capability): void
    {
        $id = (int) $userId;
        $denials = self::$explicitDenials[$id] ?? [];
        if (!in_array($capability, $denials, true)) {
            $denials[] = $capability;
            self::$explicitDenials[$id] = $denials;
        }
    }

    public static function revoke(int $userId, string $capability): void
    {
        $id = (int) $userId;
        self::$userCaps[$id] = array_values(array_filter(
            self::$userCaps[$id] ?? [],
            static fn (string $c): bool => $c !== $capability,
        ));
    }

    public static function currentCan(string $capability, mixed ...$args): bool
    {
        return self::userCan(AuthService::currentUserId(), $capability, ...$args);
    }

    /**
     * can — alias current_user_can (mapping target CapabilityService::can).
     */
    public static function can(string $capability, mixed ...$args): bool
    {
        return self::currentCan($capability, ...$args);
    }

    public static function isLoggedIn(): bool
    {
        return AuthService::isLoggedIn();
    }

    public static function isSuperAdmin(int $userId): bool
    {
        return in_array('manage_options', self::$userCaps[(int) $userId] ?? [], true);
    }

    public static function reset(): void
    {
        self::$userCaps = [];
        self::$explicitDenials = [];
    }
}