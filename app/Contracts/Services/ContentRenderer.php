<?php

declare(strict_types=1);

namespace App\Contracts\Services;

interface ContentRenderer
{
    public function render(string $template): RenderedContent;
}
