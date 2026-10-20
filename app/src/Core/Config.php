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
        self::applyUrlOverrides();
        self::$initialized = true;
    }

    /**
     * Resolve site URLs + active theme from .env, fallback to request runtime.
     *
     * - home/site/admin/content URL: base từ APP_URL (.env), fallback theo
     *   scheme+host của request hiện tại. Scheme luôn theo request để tránh
     *   mixed-content khi trang phục vụ HTTPS.
     * - stylesheet/template: từ PW_ACTIVE_THEME (.env), fallback 'presto'.
     */
    private static function applyUrlOverrides(): void
    {
        $theme = getenv('PW_ACTIVE_THEME');
        if (is_string($theme) && $theme !== '') {
            self::$values['stylesheet'] = $theme;
            self::$values['template'] = $theme;
        }

        $base = self::resolveBaseUrl();
        if ($base === null) {
            return;
        }

        self::$values['home_url'] = $base;
        self::$values['site_url'] = $base;
        self::$values['admin_url'] = $base . '/wp-admin';

        $contentPath = getenv('PW_CONTENT_URL');
        $contentPath = (is_string($contentPath) && $contentPath !== '') ? $contentPath : '/wp-content';
        self::$values['content_url'] = $base . '/' . ltrim($contentPath, '/');
    }

    /** Base URL: .env APP_URL host (ưu tiên) → fallback request runtime. */
    private static function resolveBaseUrl(): ?string
    {
        $scheme = self::requestScheme();

        // 1) .env APP_URL — lấy authority (host[:port]), thay scheme theo request
        $env = getenv('APP_URL');
        if (is_string($env) && $env !== '') {
            $authority = explode('/', preg_replace('#^https?://#i', '', $env), 2)[0];
            if ($authority !== '') {
                return ($scheme !== '' ? $scheme : 'http') . '://' . $authority;
            }
        }

        // 2) Fallback: scheme + host của request hiện tại
        $host = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? '';
        if ($host === '') {
            return null;
        }

        return ($scheme !== '' ? $scheme : 'http') . '://' . $host;
    }

    private static function requestScheme(): string
    {
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== '' && $_SERVER['HTTPS'] !== 'off') {
            return 'https';
        }
        if (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https') {
            return 'https';
        }

        return 'http';
    }

    /** @return array<string, mixed> */
    private static function defaults(): array
    {
        return [
            'home_url' => '',
            'site_url' => '',
            'admin_path' => '/wp-admin',
            'admin_url' => '',
            'content_dir' => '',
            'content_url' => '',
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