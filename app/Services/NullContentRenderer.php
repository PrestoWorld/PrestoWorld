<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\Services\ContentRenderer as ContentRendererContract;
use App\Exceptions\RenderException;

class NullContentRenderer implements ContentRendererContract
{
    public function render(string $template): string
    {
        throw new RenderException('No content renderer is available');
    }

    public function getStyles(): string
    {
        return '';
    }
}
