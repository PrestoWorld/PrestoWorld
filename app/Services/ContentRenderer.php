<?php

declare(strict_types=1);

namespace App\Services;

use PrestoWorld\Modules\Gutenberg\Module as GutenbergModule;
use PrestoWorld\Modules\Gutenberg\Renderer\BlockRenderer;
use App\Contracts\Services\ContentRenderer as ContentRendererContract;
use App\Contracts\Services\RenderedContent;

class ContentRenderer implements ContentRendererContract
{
    public function __construct(
        private GutenbergModule $gutenberg,
        private BlockRenderer $renderer,
    ) {}

    public function render(string $template, array $post = []): RenderedContent
    {
        if (!empty($post)) {
            $this->renderer->mergeContext(['post' => $post]);
        }

        return new RenderedContent(
            body: $this->gutenberg->renderTemplate($template) ?? '',
            styles: $this->gutenberg->getStyles(),
        );
    }
}
