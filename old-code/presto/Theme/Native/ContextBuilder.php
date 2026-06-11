<?php

declare(strict_types=1);

namespace PrestoWorld\Theme\Native;

class ContextBuilder
{
    protected string $themePath;

    public function __construct(string $themePath)
    {
        $this->themePath = $themePath;
    }

    public function build(): array
    {
        return [];
    }
}
