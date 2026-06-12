<?php

declare(strict_types=1);

namespace App\Http;

use App\Contracts\Http\PageRenderer as PageRendererContract;
use App\Contracts\Http\ThemeConfig;

class PageRenderer implements PageRendererContract
{
    private array $styles = [];

    public function __construct(
        private ThemeConfig $theme,
    ) {}

    public function addStyle(string $css): void
    {
        $this->styles[] = $css;
    }

    public function render(string $body, ?string $title = null): string
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
                {$this->compileStyles()}
            </style>
        </head>
        <body>
        {$body}
        </body>
        </html>
        HTML;
    }

    private function compileStyles(): string
    {
        return implode("\n", $this->styles);
    }
}
