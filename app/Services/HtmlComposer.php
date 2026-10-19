<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\Services\HtmlComposer as HtmlComposerContract;
use App\Contracts\Http\ThemeConfig;

class HtmlComposer implements HtmlComposerContract
{
    public function __construct(
        private ThemeConfig $theme,
    ) {}

    public function compose(string $body, string $styles, ?string $title = null): string
    {
        $title = $title ?? $this->theme->defaultTitle;

        // Styles may arrive as raw CSS or as full <style>/<link> markup
        // (e.g. Gutenberg theme CSS + enqueued stylesheets) — only wrap raw
        // CSS into a <style> tag; keep complete tags as-is.
        $hasStyleMarkup = str_contains($styles, '<style') || str_contains($styles, '<link');

        if ($hasStyleMarkup) {
            $headStyles = '<style>' . "\n" . $this->theme->cssReset . "\n</style>\n" . $styles;
        } else {
            $headStyles = '<style>' . "\n" . $this->theme->cssReset . "\n" . $styles . "\n</style>";
        }

        return <<<HTML
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="{$this->theme->charset}">
            <meta name="viewport" content="{$this->theme->viewport}">
            <title>{$title}</title>
            {$headStyles}
        </head>
        <body>
        {$body}
        </body>
        </html>
        HTML;
    }
}
