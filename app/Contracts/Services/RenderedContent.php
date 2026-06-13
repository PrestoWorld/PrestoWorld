<?php

declare(strict_types=1);

namespace App\Contracts\Services;

class RenderedContent
{
    public function __construct(
        public readonly string $body,
        public readonly string $styles,
    ) {}

    public static function empty(): self
    {
        return new self('', '');
    }
}
