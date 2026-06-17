<?php

/**
 * WordPress i18n, escaping & utility compatibility stubs.
 * Used when evaluating Twenty Twenty-Five PHP pattern files outside WordPress.
 */

if (!function_exists('esc_html')) {
    function esc_html(string $text): string
    {
        return htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('esc_html__')) {
    function esc_html__(string $text, string $domain = ''): string
    {
        return htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('esc_html_e')) {
    function esc_html_e(string $text, string $domain = ''): void
    {
        echo htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('esc_html_x')) {
    function esc_html_x(string $text, string $context, string $domain = ''): string
    {
        return htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('esc_url')) {
    function esc_url(string $url): string
    {
        return htmlspecialchars($url, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('esc_attr')) {
    function esc_attr(string $text): string
    {
        return htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('esc_attr__')) {
    function esc_attr__(string $text, string $domain = ''): string
    {
        return htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('wp_stubs_translate')) {
    function wp_stubs_translate(string $text, string $domain = ''): string
    {
        return $text;
    }
}

if (!function_exists('wp_stubs_translate_echo')) {
    function wp_stubs_translate_echo(string $text, string $domain = ''): void
    {
        echo $text;
    }
}

if (!function_exists('wp_stubs_translate_context')) {
    function wp_stubs_translate_context(string $text, string $context, string $domain = ''): string
    {
        return $text;
    }
}

if (!function_exists('__')) {
    function __(string $text, string $domain = ''): string
    {
        return wp_stubs_translate($text, $domain);
    }
}

if (!function_exists('_x')) {
    function _x(string $text, string $context, string $domain = ''): string
    {
        return wp_stubs_translate_context($text, $context, $domain);
    }
}

if (!function_exists('_e')) {
    function _e(string $text, string $domain = ''): void
    {
        wp_stubs_translate_echo($text, $domain);
    }
}

if (!function_exists('_n')) {
    function _n(string $single, string $plural, int $number, string $domain = ''): string
    {
        return $number === 1 ? $single : $plural;
    }
}

if (!function_exists('wp_kses_post')) {
    function wp_kses_post(string $data): string
    {
        return $data; // Permissive for rendering — tighten in production
    }
}

if (!function_exists('get_theme_file_uri')) {
    function get_theme_file_uri(string $file = ''): string
    {
        return '/content/themes/twentytwentyfive/' . ltrim($file, '/');
    }
}

if (!function_exists('site_url')) {
    function site_url(string $path = ''): string
    {
        return '/' . ltrim($path, '/');
    }
}

if (!function_exists('human_time_diff')) {
    function human_time_diff(int $from, ?int $to = null): string
    {
        if ($to === null) {
            $to = time();
        }
        $diff = abs($to - $from);
        if ($diff < 60) {
            return $diff . ' seconds';
        }
        if ($diff < 3600) {
            return round($diff / 60) . ' minutes';
        }
        if ($diff < 86400) {
            return round($diff / 3600) . ' hours';
        }
        return round($diff / 86400) . ' days';
    }
}
