<?php

declare(strict_types=1);

namespace PrestoWorld\Core;

use PrestoWorld\Core\User\UserEntity;

/**
 * AuthService — is_user_logged_in/wp_get_current_user/wp_signon... (spec 10 §10.4 Group users).
 */
final class AuthService
{
    private static ?int $currentUserId = null;
    private static ?UserEntity $currentUser = null;
    private static bool $resolved = false;

    private function __construct()
    {
    }

    public static function currentUserId(): int
    {
        self::resolve();

        return self::$currentUserId ?? 0;
    }

    public static function currentUser(): ?UserEntity
    {
        self::resolve();

        return self::$currentUser;
    }

    public static function setCurrentUser(UserEntity|int|null $user): void
    {
        if (is_int($user)) {
            self::$currentUserId = $user;
            $found = UserRepository::find($user);
            self::$currentUser = $found instanceof UserEntity ? $found : null;
            self::$resolved = true;

            return;
        }

        self::$currentUser = $user;
        self::$currentUserId = $user?->id;
        self::$resolved = true;
    }

    public static function isLoggedIn(): bool
    {
        return self::currentUserId() > 0;
    }

    public static function role(int $userId): ?string
    {
        $user = UserRepository::find($userId);

        return $user instanceof UserEntity ? ($user->roles[0] ?? null) : null;
    }

    /**
     * authenticate — kiểm tra username/password (password_verify).
     */
    public static function authenticate(string $username, string $password): bool
    {
        $user = UserRepository::findBy(['login' => $username]);
        if (!$user instanceof UserEntity) {
            $user = UserRepository::findBy(['user_login' => $username]);
        }

        if (!$user instanceof UserEntity || $user->id === 0) {
            return false;
        }

        $hash = $user->userPass;
        if ($hash === '') {
            return false;
        }

        if (!password_verify($password, $hash)) {
            return false;
        }

        self::setCurrentUser($user);

        return true;
    }

    public static function signIn(string $username, string $password, bool $remember = false): bool
    {
        $ok = self::authenticate($username, $password);
        if ($ok && $remember) {
            self::setCookie('pw_remember', self::currentUserId());
        }

        return $ok;
    }

    public static function signOut(): void
    {
        self::$currentUserId = null;
        self::$currentUser = null;
        self::$resolved = true;
        self::clearCookie('pw_remember');
        self::clearCookie('pw_session');
    }

    public static function requireLogin(string $redirect = ''): void
    {
        if (!self::isLoggedIn()) {
            $location = $redirect !== '' ? $redirect : Url::login();
            RedirectService::to($location, 302);
        }
    }

    public static function setCookie(string $name, int|string $value, int $expire = 0): void
    {
        if (headers_sent() || PHP_SAPI === 'cli') {
            $_COOKIE[$name] = (string) $value;

            return;
        }

        setcookie($name, (string) $value, $expire > 0 ? time() + $expire : 0, '/');
        $_COOKIE[$name] = (string) $value;
    }

    public static function clearCookie(string $name): void
    {
        unset($_COOKIE[$name]);

        if (!headers_sent() && PHP_SAPI !== 'cli') {
            setcookie($name, '', time() - 3600, '/');
        }
    }

    public static function parseCookie(string $name): ?string
    {
        $value = $_COOKIE[$name] ?? null;

        return is_string($value) ? $value : null;
    }

    public static function validateCookie(string $name, string $token): bool
    {
        $value = self::parseCookie($name);

        return $value !== null && hash_equals($value, $token);
    }

    public static function reset(): void
    {
        self::$currentUserId = null;
        self::$currentUser = null;
        self::$resolved = false;
    }

    private static function resolve(): void
    {
        if (self::$resolved) {
            return;
        }

        $remember = self::parseCookie('pw_remember');
        if ($remember !== null && is_numeric($remember) && (int) $remember > 0) {
            $found = UserRepository::find((int) $remember);
            self::$currentUserId = $found instanceof UserEntity ? $found->id : null;
            self::$currentUser = $found instanceof UserEntity ? $found : null;
        }

        self::$resolved = true;
    }
}