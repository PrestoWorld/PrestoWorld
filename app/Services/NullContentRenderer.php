<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\Services\ContentRenderer as ContentRendererContract;
use App\Contracts\Services\RenderedContent;

class NullContentRenderer implements ContentRendererContract
{
    public function render(string $template, array $post = []): RenderedContent
    {
        return RenderedContent::empty();
    }
}
