<?php

declare(strict_types=1);

namespace App\Services\Frontend;

/**
 * Resolves which frontend rendering pipeline is active.
 *
 * Environment variable PW_FRONTEND_MODE takes precedence over
 * config('frontend.mode'). Default is THEME (the classic theme engine);
 * projects opt into CUSTOM mode via config or env.
 */
final class FrontendMode
{
    public const CUSTOM = 'custom';

    public const THEME = 'theme';

    private static ?string $resolved = null;

    public static function current(): string
    {
        if (self::$resolved !== null) {
            return self::$resolved;
        }

        $mode = getenv('PW_FRONTEND_MODE');

        if (!is_string($mode) || $mode === '') {
            try {
                $configured = config('frontend.mode');
                $mode = is_string($configured) ? $configured : '';
            } catch (\Throwable) {
                $mode = '';
            }
        }

        self::$resolved = $mode === self::CUSTOM ? self::CUSTOM : self::THEME;

        return self::$resolved;
    }

    public static function isCustom(): bool
    {
        return self::current() === self::CUSTOM;
    }

    /**
     * Reset the memoized mode (test helper).
     */
    public static function reset(): void
    {
        self::$resolved = null;
    }
}
