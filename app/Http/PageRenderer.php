<?php

declare(strict_types=1);

namespace App\Http;

class PageRenderer
{
    private array $styles = [];

    public function addStyle(string $css): void
    {
        $this->styles[] = $css;
    }

    public function render(string $body, ?string $title = null): string
    {
        $title = $title ?? 'PrestoWorld';

        return <<<HTML
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>{$title}</title>
            <style>
                *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
                body { font-family: system-ui, sans-serif; line-height: 1.6; }
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
