<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use PrestoWorld\Theme\ThemeRepository;
use Witals\Framework\Http\Request;
use Witals\Framework\Http\Response;

class ThemesController
{
    public function themes(): Response
    {
        $themesDir = $this->themesDir();

        $repo = new ThemeRepository($themesDir);
        $themes = $repo->getAll();

        return Response::json($themes);
    }

    /**
     * List all available themes (installed + on-disk).
     *
     * GET /api/admin/themes/available
     *
     * Unlike /themes (which only returns what ThemeRepository can parse from
     * style.css), this endpoint also includes:
     *   - installed: whether the theme is in the database / active
     *   - is_active: the currently active theme
     *   - screenshot: full URL to the screenshot file
     *   - source: filesystem | database
     */
    public function available(): Response
    {
        $themesDir = $this->themesDir();
        $repo = new ThemeRepository($themesDir);

        $themes = $repo->getAll();
        $active = $repo->getActive();

        $out = [];
        foreach ($themes as $theme) {
            $dir = $theme['directory'] ?? '';

            $out[] = [
                'slug' => $dir,
                'name' => $theme['name'] ?? $dir,
                'uri' => $theme['uri'] ?? '',
                'author' => $theme['author'] ?? '',
                'author_uri' => $theme['author_uri'] ?? '',
                'description' => $theme['description'] ?? '',
                'version' => $theme['version'] ?? '0.0.0',
                'requires' => $theme['requires'] ?? '',
                'requires_php' => $theme['requires_php'] ?? '',
                'tags' => $theme['tags'] ?? '',
                'screenshot' => $repo->getScreenshotUrl($dir),
                'is_active' => $active === $dir,
                'installed' => true,
                'source' => 'filesystem',
            ];
        }

        return Response::json($out);
    }

    public function activateTheme(Request $request): Response
    {
        $body = json_decode($request->body() ?? '{}', true);
        $theme = $body['theme'] ?? '';

        if ($theme === '') {
            return Response::json(['success' => false, 'error' => 'No theme specified'], 400);
        }

        $themesDir = $this->themesDir();

        if ($themesDir === null || !is_dir($themesDir . '/' . $theme)) {
            return Response::json(['success' => false, 'error' => 'Theme not found'], 404);
        }

        putenv('PW_ACTIVE_THEME=' . $theme);
        $_ENV['PW_ACTIVE_THEME'] = $theme;
        \PrestoWorld\Core\Cache\CacheInvalidation::onContentChange('theme-activate');

        $repo = new ThemeRepository($themesDir);
        $all = $repo->getAll();
        $name = $theme;
        foreach ($all as $t) {
            if ($t['directory'] === $theme) {
                $name = $t['name'];
                break;
            }
        }

        return Response::json([
            'success' => true,
            'theme' => $theme,
            'name' => $name,
        ]);
    }

    public function activateThemeFromForm(Request $request): Response
    {
        $theme = $request->post('theme', '');

        if ($theme === '') {
            return Response::redirect('/wp-admin/themes.php');
        }

        $themesDir = $this->themesDir();

        if ($themesDir !== null && is_dir($themesDir . '/' . $theme)) {
            putenv('PW_ACTIVE_THEME=' . $theme);
            $_ENV['PW_ACTIVE_THEME'] = $theme;
            \PrestoWorld\Core\Cache\CacheInvalidation::onContentChange('theme-activate');
        }

        return Response::redirect('/wp-admin/themes.php');
    }

    private function themesDir(): ?string
    {
        $contentDir = getenv('PW_CONTENT_DIR');

        if ($contentDir !== false && $contentDir !== '') {
            return $contentDir . '/themes';
        }

        // Fallback to the default content/themes path.
        return dirname(__DIR__, 3) . '/content/themes';
    }
}