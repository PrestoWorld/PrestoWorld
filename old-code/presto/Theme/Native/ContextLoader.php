<?php

declare(strict_types=1);

namespace PrestoWorld\Theme\Native;

use PrestoWorld\Theme\Theme;

class ContextLoader
{
    protected Theme $theme;
    protected ContextBuilder $builder;

    public function __construct(Theme $theme, ContextBuilder $builder)
    {
        $this->theme = $theme;
        $this->builder = $builder;
    }

    public function load(): void
    {
        // Logic to load theme using context
    }
}
