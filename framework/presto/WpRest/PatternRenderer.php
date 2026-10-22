<?php

declare(strict_types=1);

namespace PrestoWorld\WpRest;

/**
 * Executes a theme pattern file to obtain its block markup.
 *
 * Theme patterns are PHP files: the block HTML is interleaved with
 * translation/escaping calls (`esc_html_x`, `printf`, …) and URL helpers
 * (`get_template_directory_uri`). Returning the raw source would hand the
 * Site Editor PHP instead of blocks, so the file is included with output
 * buffering and a small set of WordPress-compatible shims that are registered
 * only when a function is not already defined (the classic theme layer may
 * have loaded real stubs earlier in the request).
 */
final class PatternRenderer
{
    private static bool $shimsRegistered = false;

    public static string $themeDir = '';
    public static string $themeUrl = '';

    private const MODE_RAW = 1;
    private const MODE_ESCAPED = 2;
    private const MODE_ECHO = 3;
    private const MODE_ECHO_ESCAPED = 4;

    private function __construct()
    {
    }

    public static function render(string $file, string $themeDir, string $themeUrl): string
    {
        if (!is_file($file)) {
            return '';
        }

        self::$themeDir = $themeDir;
        self::$themeUrl = rtrim($themeUrl, '/');

        self::registerShims();

        ob_start();

        try {
            include $file;
        } catch (\Throwable) {
            ob_end_clean();

            // Fall back to the raw source with PHP regions removed so the
            // editor still receives parseable block markup.
            return self::stripPhp((string) file_get_contents($file));
        }

        return trim((string) ob_get_clean());
    }

    private static function stripPhp(string $source): string
    {
        return trim(preg_replace('/<\?php.*?\?>/s', '', $source) ?? $source);
    }

    private static function registerShims(): void
    {
        if (self::$shimsRegistered) {
            return;
        }

        self::$shimsRegistered = true;

        $shims = [
            // Translation / escaping — patterns call the echo variants inline
            // inside markup, so those four must print rather than return.
            '__' => self::MODE_RAW,
            '_x' => self::MODE_RAW,
            'esc_html__' => self::MODE_ESCAPED,
            'esc_attr__' => self::MODE_ESCAPED,
            'esc_html_x' => self::MODE_ESCAPED,
            'esc_attr_x' => self::MODE_ESCAPED,
            'esc_html' => self::MODE_ESCAPED,
            'esc_attr' => self::MODE_ESCAPED,
            'esc_url' => self::MODE_RAW,
            'wp_kses_post' => self::MODE_RAW,
            '_e' => self::MODE_ECHO,
            '_ex' => self::MODE_ECHO,
            'esc_html_e' => self::MODE_ECHO_ESCAPED,
            'esc_attr_e' => self::MODE_ECHO_ESCAPED,
        ];

        foreach ($shims as $name => $mode) {
            $body = match ($mode) {
                self::MODE_ESCAPED => "\$t = (string) (\$args[0] ?? ''); return htmlspecialchars(\$t, ENT_QUOTES, 'UTF-8');",
                self::MODE_ECHO => "echo (string) (\$args[0] ?? '');",
                self::MODE_ECHO_ESCAPED => "\$t = (string) (\$args[0] ?? ''); echo htmlspecialchars(\$t, ENT_QUOTES, 'UTF-8');",
                default => "return (string) (\$args[0] ?? '');",
            };

            self::define($name, $body);
        }

        $cls = '\\' . self::class;

        self::define('get_template_directory', "return {$cls}::\$themeDir;");
        self::define('get_stylesheet_directory', "return {$cls}::\$themeDir;");
        self::define('get_template_directory_uri', "return {$cls}::\$themeUrl;");
        self::define('get_stylesheet_directory_uri', "return {$cls}::\$themeUrl;");
        self::define('get_theme_file_uri', "return {$cls}::\$themeUrl . '/' . ltrim((string) (\$args[0] ?? ''), '/');");
        self::define('get_theme_file_path', "return {$cls}::\$themeDir . '/' . ltrim((string) (\$args[0] ?? ''), '/');");
    }

    /**
     * eval() is the only way to declare a global function at runtime; names
     * and bodies are fixed literals defined above.
     */
    private static function define(string $name, string $body): void
    {
        if (function_exists($name)) {
            return;
        }

        eval(sprintf('function %s(mixed ...$args) { %s }', $name, $body));
    }
}
