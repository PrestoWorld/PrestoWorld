<?php

declare(strict_types=1);

namespace PrestoWorld\Core\Auth;

use PrestoWorld\Core\Error\PrestoError;

/**
 * ApplicationPasswords — WP_Application_Passwords replacement (spec 10 §10.5.5).
 */
final class ApplicationPasswords
{
    /** @var array<int, array<string, array<string, mixed>>> */
    private static array $passwords = [];

    private static int $currentApplicationUser = 0;

    private function __construct()
    {
    }

    /**
     * @param array<string, mixed> $args
     * @return array{uuid: string, password: string}|PrestoError
     */
    public static function create_new_application_password(int $userId, array $args = []): array|PrestoError
    {
        if ($userId <= 0) {
            return new PrestoError('application_password_missing_user', 'A valid user ID is required.');
        }

        $uuid = self::uuid4();
        $password = self::randomPassword();
        $name = isset($args['name']) && is_string($args['name']) ? $args['name'] : 'Application Password';

        self::$passwords[$userId][$uuid] = [
            'uuid' => $uuid,
            'name' => $name,
            'password' => password_hash($password, PASSWORD_DEFAULT),
            'created' => time(),
            'last_used' => null,
            'last_ip' => null,
        ];

        return ['uuid' => $uuid, 'password' => $password];
    }

    /** @return array<int, array<string, mixed>> */
    public static function get_user_application_passwords(int $userId): array
    {
        $records = [];
        foreach (self::$passwords[$userId] ?? [] as $record) {
            unset($record['password']);
            $records[] = $record;
        }

        return $records;
    }

    /** @return array<string, mixed>|null */
    public static function get_user_application_password(int $userId, string $uuid): ?array
    {
        if (!isset(self::$passwords[$userId][$uuid])) {
            return null;
        }

        $record = self::$passwords[$userId][$uuid];
        unset($record['password']);

        return $record;
    }

    /**
     * @param array<string, mixed> $args
     * @return array{uuid: string, password: string}|PrestoError
     */
    public static function update_application_password(int $userId, string $uuid, array $args = []): array|PrestoError
    {
        if (!isset(self::$passwords[$userId][$uuid])) {
            return new PrestoError('application_password_not_found', 'Application password not found.');
        }

        $password = self::randomPassword();
        self::$passwords[$userId][$uuid]['password'] = password_hash($password, PASSWORD_DEFAULT);

        if (isset($args['name']) && is_string($args['name'])) {
            self::$passwords[$userId][$uuid]['name'] = $args['name'];
        }

        return ['uuid' => $uuid, 'password' => $password];
    }

    public static function delete_application_password(int $userId, string $uuid): bool|PrestoError
    {
        if (!isset(self::$passwords[$userId][$uuid])) {
            return new PrestoError('application_password_not_found', 'Application password not found.');
        }

        unset(self::$passwords[$userId][$uuid]);

        return true;
    }

    public static function is_user_application_auth(): bool
    {
        return self::$currentApplicationUser > 0;
    }

    public static function reset(): void
    {
        self::$passwords = [];
        self::$currentApplicationUser = 0;
    }

    private static function uuid4(): string
    {
        $hex = bin2hex(random_bytes(16));
        $hex[12] = '4';
        $hex[16] = dechex((hexdec($hex[16]) & 0x3) | 0x8);

        return sprintf(
            '%s-%s-%s-%s-%s',
            substr($hex, 0, 8),
            substr($hex, 8, 4),
            substr($hex, 12, 4),
            substr($hex, 16, 4),
            substr($hex, 20, 12),
        );
    }

    private static function randomPassword(): string
    {
        $alphabet = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $max = strlen($alphabet) - 1;
        $password = '';
        for ($i = 0; $i < 24; $i++) {
            $password .= $alphabet[random_int(0, $max)];
        }

        return $password;
    }
}
