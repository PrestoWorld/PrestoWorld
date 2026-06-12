<?php

declare(strict_types=1);

namespace App\Http;

use App\Contracts\Http\PageRenderer as PageRendererContract;

class PageRenderer implements PageRendererContract
{
    private array $styles = [];

    public function __construct(
        private string $defaultTitle = 'PrestoWorld',
        private string $charset = 'UTF-8',
        private string $viewport = 'width=device-width, initial-scale=1.0',
        private string $cssReset = '*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; } body { font-family: system-ui, sans-serif; line-height: 1.6; }',
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
