<?php

declare(strict_types=1);

namespace PrestoWorld\Theme;

class ThemeRepository
{
    private string $themesDir;

    public function __construct(?string $themesDir = null)
    {
        $this->themesDir = $themesDir
            ?? getenv('PW_CONTENT_DIR')
                ? getenv('PW_CONTENT_DIR') . '/themes'
                : (defined('PW_THEMES_DIR') ? PW_THEMES_DIR : '');
    }

    public function setThemesDir(string $path): void
    {
        $this->themesDir = $path;
    }

    public function getAll(): array
    {
        if (!is_dir($this->themesDir)) {
            return [];
        }

        $entries = scandir($this->themesDir);
        if ($entries === false) {
            return [];
        }

        $themes = [];
        foreach ($entries as $entry) {
            if ($entry[0] === '.') {
                continue;
            }
            $path = $this->themesDir . '/' . $entry;
            if (!is_dir($path)) {
                continue;
            }
            $headers = $this->readStyleHeaders($path . '/style.css');
            if ($headers === null) {
                continue;
            }
            $themes[] = $headers;
        }

        usort($themes, fn(array $a, array $b) => strcmp($a['name'], $b['name']));

        return $themes;
    }

    public function getActive(): ?string
    {
        return getenv('PW_THEME_ACTIVE') ?: getenv('PW_ACTIVE_THEME') ?: null;
    }

    public function getActiveTheme(): ?array
    {
        $active = $this->getActive();
        if ($active === null) {
            return null;
        }
        $path = $this->themesDir . '/' . $active . '/style.css';
        return $this->readStyleHeaders($path);
    }

    public function getScreenshotUrl(string $themeDir): ?string
    {
        $candidates = ['screenshot.png', 'screenshot.jpg', 'screenshot.jpeg'];
        foreach ($candidates as $name) {
            $path = $this->themesDir . '/' . $themeDir . '/' . $name;
            if (file_exists($path)) {
                $contentUrl = getenv('PW_CONTENT_URL') ?: '/wp-content';
                return $contentUrl . '/themes/' . $themeDir . '/' . $name;
            }
        }
        return null;
    }

    private function readStyleHeaders(string $stylePath): ?array
    {
        if (!file_exists($stylePath)) {
            return null;
        }

        $content = file_get_contents($stylePath);
        if ($content === false) {
            return null;
        }

        $headers = [
            'name' => 'Theme Name',
            'uri' => 'Theme URI',
            'author' => 'Author',
            'author_uri' => 'Author URI',
            'description' => 'Description',
            'version' => 'Version',
            'requires' => 'Requires at least',
            'requires_php' => 'Requires PHP',
            'tested' => 'Tested up to',
            'tags' => 'Tags',
            'text_domain' => 'Text Domain',
            'license' => 'License',
            'license_uri' => 'License URI',
        ];

        $result = [
            'directory' => basename(dirname($stylePath)),
        ];

        foreach ($headers as $key => $header) {
            $result[$key] = self::parseHeader($content, $header);
        }

        if ($result['name'] === null) {
            return null;
        }

        $result['screenshot'] = $this->getScreenshotUrl($result['directory']);
        $result['is_active'] = $result['directory'] === $this->getActive();

        return $result;
    }

    private static function parseHeader(string $content, string $name): ?string
    {
        $pattern = '/^[ \t\/*#]*' . preg_quote($name, '/') . ':\s*(.+)$/mi';
        if (preg_match($pattern, $content, $matches)) {
            return trim($matches[1]);
        }
        return null;
    }
}