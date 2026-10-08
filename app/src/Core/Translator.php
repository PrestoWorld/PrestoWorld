<?php

declare(strict_types=1);

namespace PrestoWorld\Core;

/**
 * Translator — __/_e/_x/_n... (spec 10 §10.4 Group i18n + §10.8).
 *
 * Domain lưu entry map {msgid|msgid\x04ctx: translation}. load() đọc file
 * .mo không tự parse — accepts PHP/JSON translation file, fallback noop.
 */
final class Translator
{
    private const CONTEXT_SEP = "\x04";

    /** @var array<string, array<string, string>> */
    private static array $domains = [];

    /** @var array<string, bool> */
    private static array $loaded = [];

    private static string $locale = 'en_US';

    private function __construct()
    {
    }

    public static function __(string $text, string $domain = 'default'): string
    {
        return self::lookup($text, $domain);
    }

    public static function _e(string $text, string $domain = 'default'): void
    {
        echo self::__($text, $domain);
    }

    public static function _x(string $text, string $context, string $domain = 'default'): string
    {
        return self::withContext($text, $context, $domain);
    }

    public static function _ex(string $text, string $context, string $domain = 'default'): void
    {
        echo self::withContext($text, $context, $domain);
    }

    public static function _n(string $single, string $plural, int $number, string $domain = 'default'): string
    {
        return self::pluralize($single, $plural, $number, $domain);
    }

    public static function _nx(string $single, string $plural, int $number, string $context, string $domain = 'default'): string
    {
        return self::pluralizeContext($single, $plural, $number, $context, $domain);
    }

    public static function _nNoop(string $single, string $plural, string $domain = 'default'): string
    {
        return $single;
    }

    public static function _nxNoop(string $single, string $plural, string $context, string $domain = 'default'): string
    {
        return $single;
    }

    public static function translate(string $text, string $domain = 'default'): string
    {
        return self::lookup($text, $domain);
    }

    public static function withContext(string $text, string $context, string $domain = 'default'): string
    {
        $key = $text . self::CONTEXT_SEP . $context;

        return self::lookupKey($key, $domain) ?? $text;
    }

    public static function load(string $domain, string $file): bool
    {
        if (self::$loaded[$domain] ?? false) {
            return true;
        }

        if (!is_file($file)) {
            return false;
        }

        $raw = file_get_contents($file);
        if ($raw === false) {
            return false;
        }

        $data = null;
        if (str_ends_with($file, '.json')) {
            try {
                /** @var array<string, string> $decoded */
                $decoded = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
                $data = $decoded;
            } catch (\JsonException) {
                return false;
            }
        } else {
            $data = include $file;
            if (!is_array($data)) {
                return false;
            }
        }

        self::$domains[$domain] = array_merge(self::$domains[$domain] ?? [], $data);
        self::$loaded[$domain] = true;

        return true;
    }

    public static function loadPluginDomain(string $domain, string $file): bool
    {
        return self::load($domain, $file);
    }

    public static function loadThemeDomain(string $domain, string $file): bool
    {
        return self::load($domain, $file);
    }

    public static function unload(string $domain): void
    {
        unset(self::$domains[$domain], self::$loaded[$domain]);
    }

    public static function isLoaded(string $domain): bool
    {
        return self::$loaded[$domain] ?? false;
    }

    /**
     * dget — get translation theo domain (alias translate).
     */
    public static function dget(string $text, string $domain = 'default'): string
    {
        return self::lookup($text, $domain);
    }

    /**
     * dnget — plural theo domain.
     */
    public static function dnget(string $single, string $plural, int $number, string $domain = 'default'): string
    {
        return self::pluralize($single, $plural, $number, $domain);
    }

    public static function locale(): string
    {
        return self::$locale;
    }

    public static function setLocale(string $locale): void
    {
        self::$locale = $locale;
        Config::set('locale', $locale);
    }

    /** @param array<string, string> $entries */
    public static function setEntries(string $domain, array $entries): void
    {
        self::$domains[$domain] = array_merge(self::$domains[$domain] ?? [], $entries);
        self::$loaded[$domain] = true;
    }

    public static function reset(): void
    {
        self::$domains = [];
        self::$loaded = [];
        self::$locale = Config::string('locale', 'en_US');
    }

    private static function lookup(string $text, string $domain): string
    {
        return self::lookupKey($text, $domain) ?? $text;
    }

    private static function lookupKey(string $key, string $domain): ?string
    {
        return self::$domains[$domain][$key] ?? self::$domains['default'][$key] ?? null;
    }

    private static function pluralize(string $single, string $plural, int $number, string $domain): string
    {
        $key = $number === 1 ? $single : $plural;

        return self::lookupKey($key, $domain) ?? ($number === 1 ? $single : $plural);
    }

    private static function pluralizeContext(string $single, string $plural, int $number, string $context, string $domain): string
    {
        $key = ($number === 1 ? $single : $plural) . self::CONTEXT_SEP . $context;

        return self::lookupKey($key, $domain) ?? ($number === 1 ? $single : $plural);
    }
}