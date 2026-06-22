<?php

declare(strict_types=1);

namespace App\Contracts\Services;

class RenderedContent
{
    public function __construct(
        public readonly string $body,
        public readonly string $styles,
        public readonly bool $complete = false,
    ) {}

    public static function empty(): self
    {
        return new self('', '');
    }

    public static function complete(string $html, string $styles = ''): self
    {
        return new self($html, $styles, complete: true);
    }
}
