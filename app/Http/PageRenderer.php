<?php

declare(strict_types=1);

namespace App\Http;

use App\Contracts\Http\PageRenderer as PageRendererContract;

class PageRenderer implements PageRendererContract
{
    private array $styles = [];

    public function __construct(
        private string $defaultTitle,
        private string $charset,
        private string $viewport,
        private string $cssReset,
    ) {}

    public function addStyle(string $css): void
    {
        $this->styles[] = $css;
    }

    public function render(string $body, ?string $title = null): string
    {
        $title = $title ?? $this->defaultTitle;

        return <<<HTML
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="{$this->charset}">
            <meta name="viewport" content="{$this->viewport}">
            <title>{$title}</title>
            <style>
                {$this->cssReset}
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
