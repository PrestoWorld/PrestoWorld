<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Witals\Framework\Application;
use Witals\Framework\Http\Request;
use Witals\Framework\Http\Response;

/**
 * Serves static files from content/ (themes, plugins, uploads) with a
 * strict extension whitelist — PHP sources are never exposed or executed.
 */
class ContentFileController
{
    /** Extensions safe to serve statically. */
    private const ALLOWED = [
        'css' => 'text/css; charset=UTF-8',
        'js' => 'application/javascript; charset=UTF-8',
        'mjs' => 'application/javascript; charset=UTF-8',
        'json' => 'application/json; charset=UTF-8',
        'map' => 'application/json; charset=UTF-8',
        'txt' => 'text/plain; charset=UTF-8',
        'xml' => 'application/xml',
        'svg' => 'image/svg+xml',
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'gif' => 'image/gif',
        'webp' => 'image/webp',
        'avif' => 'image/avif',
        'ico' => 'image/x-icon',
        'woff' => 'font/woff',
        'woff2' => 'font/woff2',
        'ttf' => 'font/ttf',
        'otf' => 'font/otf',
        'eot' => 'application/vnd.ms-fontobject',
        'mp4' => 'video/mp4',
        'webm' => 'video/webm',
        'mp3' => 'audio/mpeg',
        'wasm' => 'application/wasm',
        'pdf' => 'application/pdf',
    ];

    public function __construct(
        private readonly Application $app,
    ) {
    }

    public function serve(Request $request, string $path): Response
    {
        $base = realpath($this->app->basePath('content'));
        $file = ($base !== false && $base !== '')
            ? realpath($base . '/' . ltrim($path, '/'))
            : false;

        // Containment check: realpath resolves ../ and symlinks, so a file
        // outside content/ can never pass str_starts_with.
        if (
            $file === false
            || $base === false
            || $base === ''
            || !is_file($file)
            || !str_starts_with($file, $base . DIRECTORY_SEPARATOR)
        ) {
            return Response::html('Not Found', 404);
        }

        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        $mime = self::ALLOWED[$ext] ?? null;
        if ($mime === null) {
            return Response::html('Forbidden', 403);
        }

        $content = file_get_contents($file);
        if ($content === false) {
            return Response::html('Not Found', 404);
        }

        return new Response($content, 200, [
            'Content-Type' => $mime,
            'Cache-Control' => 'public, max-age=3600',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
