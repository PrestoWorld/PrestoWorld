<?php

declare(strict_types=1);

namespace PrestoWorld\Core;

/**
 * Cấu hình runtime PrestoWorld\Core (copy của WP constants/settings).
 *
 * Static holder, test thiết lập trực tiếp qua set(); reset() giữa request
 * (spec 10 §10.7.2). Nếu container có config() thì ưu tiên bind từ đó —
 * dùng config() của app khi khả dụng, nếu không dùng default.
 */
final class Config
{
    /** @var array<string, mixed> */
    private static array $values = [];

    private static bool $initialized = false;

    private function __construct()
    {
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        self::init();

        return self::$values[$key] ?? $default;
    }

    public static function string(string $key, string $default = ''): string
    {
        $value = self::get($key, $default);

        return is_scalar($value) ? (string) $value : $default;
    }

    public static function int(string $key, int $default = 0): int
    {
        $value = self::get($key, $default);

        return is_scalar($value) ? (int) $value : $default;
    }

    public static function set(string $key, mixed $value): void
    {
        self::init();
        self::$values[$key] = $value;
    }

    public static function has(string $key): bool
    {
        self::init();

        return isset(self::$values[$key]);
    }

    public static function reset(): void
    {
        self::$values = [];
        self::$initialized = false;
        self::init();
    }

    private static function init(): void
    {
        if (self::$initialized) {
            return;
        }

        self::$values = array_merge(self::defaults(), self::$values);
        self::$initialized = true;
    }

    /** @return array<string, mixed> */
    private static function defaults(): array
    {
        return [
            'home_url' => 'https://example.com',
            'site_url' => 'https://example.com',
            'admin_path' => '/wp-admin',
            'admin_url' => 'https://example.com/wp-admin',
            'content_dir' => '',
            'content_url' => 'https://example.com/wp-content',
            'plugin_dir' => '',
            'plugin_url' => '',
            'theme_dir' => '',
            'theme_url' => '',
            'uploads_dir' => '',
            'uploads_url' => '',
            'stylesheet' => 'presto',
            'template' => 'presto',
            'timezone_string' => date_default_timezone_get(),
            'gmt_offset' => (float) date('Z') / 3600,
            'locale' => 'en_US',
            'charset' => 'UTF-8',
            'db_prefix' => 'pw_',
            'wp_version' => '6.4.3',
            'php_version' => PHP_VERSION,
            'blogname' => 'PrestoWorld',
            'blogdescription' => 'Just another PrestoWorld site',
            'language' => 'en_US',
        ];
    }
}