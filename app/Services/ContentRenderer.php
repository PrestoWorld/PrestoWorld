<?php

declare(strict_types=1);

namespace App\Services;

use PrestoWorld\Modules\Gutenberg\Module as GutenbergModule;
use App\Contracts\Services\ContentRenderer as ContentRendererContract;
use App\Contracts\Services\RenderedContent;

class ContentRenderer implements ContentRendererContract
{
    public function __construct(
        private GutenbergModule $gutenberg,
    ) {}

    public function render(string $template): RenderedContent
    {
        return new RenderedContent(
            body: $this->gutenberg->renderTemplate($template) ?? '',
            styles: $this->gutenberg->getStyles(),
        );
    }
}
