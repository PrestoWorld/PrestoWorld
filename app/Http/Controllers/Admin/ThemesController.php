<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use Witals\Framework\Http\Request;
use Witals\Framework\Http\Response;
use PrestoWorld\Theme\ThemeRepository;

class ThemesController
{
    public function themes(): Response
    {
        $themesDir = getenv('PW_CONTENT_DIR')
            ? getenv('PW_CONTENT_DIR') . '/themes'
            : null;

        $repo = new ThemeRepository($themesDir);
        $themes = $repo->getAll();

        return Response::json($themes);
    }

    public function activateTheme(Request $request): Response
    {
        $body = json_decode($request->body() ?? '{}', true);
        $theme = $body['theme'] ?? '';

        if ($theme === '') {
            return Response::json(['success' => false, 'error' => 'No theme specified'], 400);
        }

        $themesDir = getenv('PW_CONTENT_DIR')
            ? getenv('PW_CONTENT_DIR') . '/themes'
            : null;

        if ($themesDir === null || !is_dir($themesDir . '/' . $theme)) {
            return Response::json(['success' => false, 'error' => 'Theme not found'], 404);
        }

        putenv('PW_ACTIVE_THEME=' . $theme);
        $_ENV['PW_ACTIVE_THEME'] = $theme;

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

        $themesDir = getenv('PW_CONTENT_DIR')
            ? getenv('PW_CONTENT_DIR') . '/themes'
            : null;

        if ($themesDir !== null && is_dir($themesDir . '/' . $theme)) {
            putenv('PW_ACTIVE_THEME=' . $theme);
            $_ENV['PW_ACTIVE_THEME'] = $theme;
        }

        return Response::redirect('/wp-admin/themes.php');
    }
}
