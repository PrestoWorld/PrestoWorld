<?php

declare(strict_types=1);

namespace PrestoWorld\Core;

/**
 * SiteInfo — get_bloginfo (spec 10 §10.4).
 */
final class SiteInfo
{
    private function __construct()
    {
    }

    public static function get(string $show = ''): mixed
    {
        return match ($show) {
            'name' => Config::string('blogname'),
            'description' => Config::string('blogdescription'),
            'wpurl' => Config::string('site_url'),
            'url' => Config::string('home_url'),
            'admin_email' => Config::string('admin_email', 'admin@example.com'),
            'charset' => Config::string('charset', 'UTF-8'),
            'version' => Config::string('wp_version'),
            'html_type' => 'text/html',
            'language' => Config::string('language', 'en_US'),
            'stylesheet_url' => 'pw-style-stylesheet_url-placeholder',
            'title' => Config::string('blogname'),
            default => '',
        };
    }

    public static function pluginsUrl(): string
    {
        return rtrim(Config::string('plugin_url', Config::string('content_url') . '/plugins'), '/');
    }

    public static function pluginsDir(): string
    {
        return rtrim(Config::string('plugin_dir', Config::string('content_dir') . '/plugins'), '/');
    }

    public static function themeUrl(): string
    {
        return rtrim(Config::string('theme_url', Config::string('content_url') . '/themes'), '/');
    }

    public static function themeDir(): string
    {
        return rtrim(Config::string('theme_dir', Config::string('content_dir') . '/themes'), '/');
    }

    public static function uploadsUrl(): string
    {
        return rtrim(Config::string('uploads_url', Config::string('content_url') . '/uploads'), '/');
    }

    public static function uploadsDir(): string
    {
        return rtrim(Config::string('uploads_dir', Config::string('content_dir') . '/uploads'), '/');
    }
}