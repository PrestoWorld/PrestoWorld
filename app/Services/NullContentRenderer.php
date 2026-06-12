<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\Services\ContentRenderer as ContentRendererContract;

class NullContentRenderer implements ContentRendererContract
{
    public function render(string $template): string
    {
        return '';
    }

    public function getStyles(): string
    {
        return '';
    }
}
