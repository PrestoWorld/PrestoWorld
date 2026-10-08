<?php

declare(strict_types=1);

namespace PrestoWorld\Core;

/**
 * Url — tương đương home_url/site_url/admin_url/... (spec 10 §10.4 Group url).
 */
final class Url
{
    private function __construct()
    {
    }

    public static function home(string $path = '', ?string $scheme = null): string
    {
        return self::join(self::schemeApply(Config::string('home_url'), $scheme), $path);
    }

    public static function site(string $path = '', ?string $scheme = null): string
    {
        return self::join(self::schemeApply(Config::string('site_url'), $scheme), $path);
    }

    public static function admin(string $path = '', string $scheme = 'admin'): string
    {
        return self::join(Config::string('admin_url'), $path);
    }

    public static function selfAdmin(string $path = ''): string
    {
        return self::admin($path);
    }

    public static function content(string $path = ''): string
    {
        return self::join(Config::string('content_url'), $path);
    }

    public static function includes(string $path = ''): string
    {
        return self::content('includes/' . ltrim($path, '/'));
    }

    public static function permalink(int|string $postId, string $slug = ''): string
    {
        if ($slug === '') {
            return self::baseForPost((int) $postId);
        }

        return self::home($slug . '/');
    }

    public static function attachment(int|string $postId): string
    {
        return self::baseForPost((int) $postId);
    }

    public static function authorArchive(int $authorId, string $authorName = ''): string
    {
        return self::home('author/' . ($authorName !== '' ? $authorName : (string) $authorId) . '/');
    }

    public static function category(int|string $catId, string $slug = ''): string
    {
        return self::home('category/' . ($slug !== '' ? $slug : (string) $catId) . '/');
    }

    public static function tag(int|string $tagId, string $slug = ''): string
    {
        return self::home('tag/' . ($slug !== '' ? $slug : (string) $tagId) . '/');
    }

    public static function term(string $taxonomy, int|string $termId, string $slug = ''): string
    {
        return self::home($taxonomy . '/' . ($slug !== '' ? $slug : (string) $termId) . '/');
    }

    public static function search(string $query): string
    {
        return self::home('?s=' . rawurlencode($query));
    }

    public static function postTypeArchive(string $postType, string $year = '', string $month = ''): string
    {
        if ($year === '') {
            return self::home($postType . '/');
        }

        return self::home($postType . '/' . $year . '/' . ($month !== '' ? $month . '/' : ''));
    }

    public static function login(string $redirect = ''): string
    {
        $url = self::admin('login.php');
        if ($redirect !== '') {
            $url = self::addQueryArg(['redirect_to' => $redirect], $url);
        }

        return $url;
    }

    public static function logout(string $redirect = ''): string
    {
        $url = wp_logout_fallback();
        if ($redirect !== '') {
            $url = self::addQueryArg(['redirect_to' => $redirect], $url);
        }

        return $url;
    }

    public static function register(string $redirect = ''): string
    {
        return self::admin('admin.php?page=pw-register');
    }

    public static function lostPassword(string $redirect = ''): string
    {
        return self::admin('admin.php?page=pw-lostpassword');
    }

    public static function setScheme(string $url, ?string $scheme = 'https'): string
    {
        return self::schemeApply($url, $scheme);
    }

    /**
     * add_query_arg — $args mảng/string key hoặc cặp (key, value).
     *
     * @param array<string, mixed>|string $key
     */
    public static function addQueryArg(array|string $key, mixed $value = '', ?string $url = null): string
    {
        $url ??= self::home();
        $parts = parse_url($url);
        $query = [];
        if ($parts !== false) {
            if (isset($parts['query']) && $parts['query'] !== '') {
                parse_str($parts['query'], $query);
            }
            $base = self::rebuildUrl($parts);
        } else {
            $base = $url;
        }

        if (is_array($key)) {
            $query = array_merge($query, $key);
        } else {
            $query[(string) $key] = (string) $value;
        }

        return $base . '?' . http_build_query($query);
    }

    /**
     * @param list<string>|string $key
     */
    public static function removeQueryArg(array|string $key, ?string $url = null): string
    {
        $url ??= self::home();
        $parts = parse_url($url);
        if ($parts === false || !isset($parts['query'])) {
            return $url;
        }

        $query = [];
        parse_str($parts['query'], $query);
        $keys = (array) $key;
        foreach ($keys as $k) {
            unset($query[$k]);
        }

        $base = self::rebuildUrl($parts);

        return $query === [] ? $base : $base . '?' . http_build_query($query);
    }

    public static function getQueryArg(string $key, mixed $default = null, ?string $url = null): mixed
    {
        $url ??= self::home();
        $parts = parse_url($url);
        if ($parts === false || !isset($parts['query'])) {
            return $default;
        }

        parse_str($parts['query'], $query);

        return $query[$key] ?? $default;
    }

    private static function join(string $base, string $path): string
    {
        if ($path === '') {
            return rtrim($base, '/') . '/';
        }

        return rtrim($base, '/') . '/' . ltrim($path, '/');
    }

    private static function baseForPost(int $postId): string
    {
        $permalinkBase = Config::string('permalink_structure', '/%year%/%monthnum%/%postname%/');
        if (trim($permalinkBase) === '' || str_contains($permalinkBase, '%postname%') === false) {
            return self::home('?p=' . $postId);
        }

        return self::home('p/' . $postId . '/');
    }

    /**
     * @param array{scheme?: string, host?: string, path?: string, user?: string, pass?: string, fragment?: string} $parts
     */
    private static function rebuildUrl(array $parts): string
    {
        $url = '';
        if (isset($parts['scheme'])) {
            $url .= $parts['scheme'] . '://';
        }
        if (isset($parts['user'])) {
            $url .= $parts['user'];
            if (isset($parts['pass'])) {
                $url .= ':' . $parts['pass'];
            }
            $url .= '@';
        }
        if (isset($parts['host'])) {
            $url .= $parts['host'];
        }
        if (isset($parts['path'])) {
            $url .= $parts['path'];
        }
        if (isset($parts['fragment'])) {
            $url .= '#' . $parts['fragment'];
        }

        return $url;
    }

    private static function schemeApply(string $url, ?string $scheme): string
    {
        if ($scheme === null || $scheme === '') {
            return $url;
        }

        $normalized = preg_replace('#^[a-z][a-z0-9.+-]*://#i', $scheme . '://', $url) ?? $url;

        return $normalized;
    }
}

if (!function_exists('wp_logout_fallback')) {
    /**
     * URL logout (helper nội bộ để Url::logout không phụ thuộc shim).
     */
    function wp_logout_fallback(): string
    {
        return \PrestoWorld\Core\Url::admin('admin.php?page=pw-logout');
    }
}