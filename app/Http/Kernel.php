<?php

declare(strict_types=1);

namespace App\Http;

use Witals\Framework\Contracts\Http\Kernel as KernelContract;
use Witals\Framework\Http\Request;
use Witals\Framework\Http\Response;
use PrestoWorld\Modules\Gutenberg\Module as GutenbergModule;
use PrestoWorld\Modules\Gutenberg\Theme\ThemeJson;

/**
 * HTTP Kernel - Renders Twenty Twenty-Five via Gutenberg Engine
 */
class Kernel implements KernelContract
{
    public function handle(Request $request): Response
    {
        /** @var GutenbergModule $gutenberg */
        $gutenberg = app(GutenbergModule::class);

        // Choose template based on request path
        $path = rtrim($request->path(), '/');
        $template = match (true) {
            $path === '' || $path === '/' => 'index',
            str_starts_with($path, '/search') => 'search',
            default => 'index',
        };

        // Generate full HTML page
        $css  = $gutenberg->getStyles();
        $body = $gutenberg->renderTemplate($template);
        $html = $this->wrapHtml($css, $body);

        return new Response($html, 200, ['Content-Type' => 'text/html; charset=utf-8']);
    }

    protected function wrapHtml(string $css, string $body): string
    {
        return <<<HTML
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>PrestoWorld</title>
            <style>
                *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
                body { font-family: system-ui, sans-serif; line-height: 1.6; }
                .wp-block-group.alignfull { width: 100%; }
                .wp-block-group.alignwide { max-width: var(--wp--style--global--wide-size, 1200px); margin: 0 auto; }
                .wp-block-group[style*="constrained"], .layout-constrained { max-width: var(--wp--style--global--content-size, 800px); margin: 0 auto; padding-left: 1.5rem; padding-right: 1.5rem; }
                nav ul { list-style: none; display: flex; gap: 1.5rem; }
                nav a { text-decoration: none; color: inherit; }
                .wp-block-columns { display: flex; flex-wrap: wrap; gap: 1.5rem; }
                .wp-block-column { flex: 1 1 0; }
                .wp-block-site-title a { font-weight: 700; font-size: 1.25rem; text-decoration: none; color: inherit; }
                {$css}
            </style>
        </head>
        <body>
        {$body}
        </body>
        </html>
        HTML;
    }
}
