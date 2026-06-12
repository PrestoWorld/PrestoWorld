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

        return <<<HTML
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="{$this->theme->charset}">
            <meta name="viewport" content="{$this->theme->viewport}">
            <title>{$title}</title>
            <style>
                {$this->theme->cssReset}
                {$styles}
            </style>
        </head>
        <body>
        {$body}
        </body>
        </html>
        HTML;
    }
}
