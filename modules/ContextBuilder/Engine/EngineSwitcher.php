<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\ContextBuilder\Engine;

/**
 * EngineSwitcher — switches between WordPress and PrestoWorld engines.
 *
 * Design Pattern: Strategy + Factory
 *
 * Automatically detects which engine is available and returns the appropriate
 * ThemeEngine implementation. This allows themes (like jankx) to run on both
 * WordPress and PrestoWorld without modification.
 *
 * Usage:
 *   $engine = EngineSwitcher::getEngine();
 *   $html = $engine->renderTemplate('index.html', $data);
 */
class EngineSwitcher
{
    protected static ?ThemeEngine $engine = null;

    /**
     * Get the active theme engine.
     *
     * Priority:
     *   1. PrestoWorld (if running on PrestoWorld)
     *   2. WordPress (if running on WordPress)
     *
     * @param array<string, mixed> $options
     */
    public static function getEngine(array $options = []): ThemeEngine
    {
        if (self::$engine !== null) {
            return self::$engine;
        }

        // Check if PrestoWorld is available
        $prestoWorld = new PrestoWorldEngine(
            self::createBlockRenderer(),
            self::getThemeDir($options),
            self::getStorageDir($options),
        );

        if ($prestoWorld->isAvailable()) {
            self::$engine = $prestoWorld;
            return self::$engine;
        }

        // Fallback to WordPress
        $wordpress = new WordPressEngine();
        if ($wordpress->isAvailable()) {
            self::$engine = $wordpress;
            return self::$engine;
        }

        // Default to PrestoWorld
        self::$engine = $prestoWorld;
        return self::$engine;
    }

    /**
     * Force a specific engine.
     */
    public static function setEngine(ThemeEngine $engine): void
    {
        self::$engine = $engine;
    }

    /**
     * Reset engine (useful for testing).
     */
    public static function reset(): void
    {
        self::$engine = null;
    }

    /**
     * Check if running on PrestoWorld.
     */
    public static function isPrestoWorld(): bool
    {
        return self::getEngine()->getName() === 'prestoworld';
    }

    /**
     * Check if running on WordPress.
     */
    public static function isWordPress(): bool
    {
        return self::getEngine()->getName() === 'wordpress';
    }

    protected static function createBlockRenderer(): BlockRenderer
    {
        // In real implementation, this would be injected via DI container
        // For now, create a basic instance
        return new BlockRenderer();
    }

    /**
     * @param array<string, mixed> $options
     */
    protected static function getThemeDir(array $options): string
    {
        if (isset($options['theme_dir'])) {
            return (string) $options['theme_dir'];
        }

        // Default to jankx theme
        $baseDir = defined('ABSPATH') ? ABSPATH : dirname(__DIR__, 6);
        return $baseDir . '/content/themes/jankx';
    }

    /**
     * @param array<string, mixed> $options
     */
    protected static function getStorageDir(array $options): string
    {
        if (isset($options['storage_dir'])) {
            return (string) $options['storage_dir'];
        }

        $baseDir = defined('ABSPATH') ? ABSPATH : dirname(__DIR__, 6);
        return $baseDir . '/storage/contexts';
    }
}