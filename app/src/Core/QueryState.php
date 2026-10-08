<?php

declare(strict_types=1);

namespace PrestoWorld\Core;

/**
 * QueryState — is_home/is_single/... (spec 10 §10.4 Group queries).
 *
 * Trạng thái request-scoped: LegacyState::reset() hoặc reset() giữa request.
 */
final class QueryState
{
    /** @var array<string, bool> */
    private static array $flags = [];

    /** @var array<string, mixed> */
    private static array $context = [];

    private function __construct()
    {
    }

    public static function set(string $flag, bool $value): void
    {
        self::$flags[$flag] = $value;
    }

    public static function setContext(string $key, mixed $value): void
    {
        self::$context[$key] = $value;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return self::$context[$key] ?? $default;
    }

    public static function isFrontPage(): bool
    {
        return (bool) (self::$flags['front_page'] ?? self::$flags['home'] ?? false);
    }

    public static function isHome(): bool
    {
        return (bool) (self::$flags['home'] ?? false);
    }

    public static function isSingle(): bool
    {
        return (bool) (self::$flags['single'] ?? false);
    }

    public static function isPage(): bool
    {
        return (bool) (self::$flags['page'] ?? false);
    }

    public static function isSingular(): bool
    {
        return (bool) (self::$flags['singular'] ?? (self::$flags['single'] ?? self::$flags['page'] ?? false));
    }

    public static function isAttachment(): bool
    {
        return (bool) (self::$flags['attachment'] ?? false);
    }

    public static function isSticky(): bool
    {
        return (bool) (self::$flags['sticky'] ?? false);
    }

    public static function isArchive(): bool
    {
        return (bool) (self::$flags['archive'] ?? false);
    }

    public static function isCategory(): bool
    {
        return (bool) (self::$flags['category'] ?? false);
    }

    public static function isTag(): bool
    {
        return (bool) (self::$flags['tag'] ?? false);
    }

    public static function isTax(): bool
    {
        return (bool) (self::$flags['tax'] ?? false);
    }

    public static function isAuthor(): bool
    {
        return (bool) (self::$flags['author'] ?? false);
    }

    public static function isDate(): bool
    {
        return (bool) (self::$flags['date'] ?? false);
    }

    public static function is404(): bool
    {
        return (bool) (self::$flags['404'] ?? false);
    }

    public static function isSearch(): bool
    {
        return (bool) (self::$flags['search'] ?? false);
    }

    public static function isFeed(): bool
    {
        return (bool) (self::$flags['feed'] ?? false);
    }

    public static function isPaged(): bool
    {
        return (bool) (self::$flags['paged'] ?? false);
    }

    public static function isMainQuery(): bool
    {
        return (bool) (self::$flags['main_query'] ?? true);
    }

    public static function isCustomizePreview(): bool
    {
        return (bool) (self::$flags['customize_preview'] ?? false);
    }

    public static function reset(): void
    {
        self::$flags = [];
        self::$context = [];
    }
}