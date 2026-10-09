<?php

declare(strict_types=1);

namespace PrestoWorld\Core\I18n;

use PrestoWorld\Core\Translator;

/**
 * TranslationController — replaces WP_Translation_Controller.
 */
final class TranslationController
{
    /** @var array<string, string> */
    private static array $overrides = [];

    private function __construct()
    {
    }

    public static function loadTextdomain(string $domain, string $path = ''): bool
    {
        if ($path === '') {
            return TextdomainRegistry::is_loaded($domain) || Translator::isLoaded($domain);
        }

        $loaded = Translator::load($domain, $path);
        if ($loaded) {
            TextdomainRegistry::set($domain, $path);
        }

        return $loaded;
    }

    public static function translate(string $text, string $domain = 'default'): string
    {
        return self::$overrides[$text] ?? Translator::translate($text, $domain);
    }

    public static function translatePlural(string $single, string $plural, int $number, string $domain = 'default'): string
    {
        $key = $number === 1 ? $single : $plural;

        return self::$overrides[$key] ?? Translator::_n($single, $plural, $number, $domain);
    }

    public static function translateCount(int $number, string $single, string $plural, string $domain = 'default'): string
    {
        return self::translatePlural($single, $plural, $number, $domain);
    }

    /**
     * @return array<string, string>
     */
    public static function all(): array
    {
        return self::$overrides;
    }

    public static function set(string $text, string $translation): void
    {
        self::$overrides[$text] = $translation;
    }

    public static function reset(): void
    {
        self::$overrides = [];
    }

    public static function load_textdomain(string $domain, string $path = ''): bool
    {
        return self::loadTextdomain($domain, $path);
    }

    public static function translate_plural(string $single, string $plural, int $number, string $domain = 'default'): string
    {
        return self::translatePlural($single, $plural, $number, $domain);
    }

    public static function translate_count(int $number, string $single, string $plural, string $domain = 'default'): string
    {
        return self::translateCount($number, $single, $plural, $domain);
    }
}
