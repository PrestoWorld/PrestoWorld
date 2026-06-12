<?php

declare(strict_types=1);

namespace App\Contracts\Http;

class ThemeConfig
{
    public function __construct(
        public readonly string $defaultTitle,
        public readonly string $charset,
        public readonly string $viewport,
        public readonly string $cssReset,
    ) {}

    public static function fromArray(array $config): self
    {
        return new self(
            defaultTitle: $config['default_title'] ?? 'PrestoWorld',
            charset: $config['charset'] ?? 'UTF-8',
            viewport: $config['viewport'] ?? 'width=device-width, initial-scale=1.0',
            cssReset: $config['css_reset'] ?? '*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; } body { font-family: system-ui, sans-serif; line-height: 1.6; }',
        );
    }
}
