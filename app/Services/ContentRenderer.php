<?php

declare(strict_types=1);

namespace App\Services;

use PrestoWorld\Theme\ThemeEngineFactory;
use App\Contracts\Services\ContentRenderer as ContentRendererContract;
use App\Contracts\Services\RenderedContent;

class ContentRenderer implements ContentRendererContract
{
    public function __construct(
        private ThemeEngineFactory $factory,
    ) {}

    public function render(string $template, array $post = []): RenderedContent
    {
        $engine = $this->factory->create();

        return $engine->render($template, $post);
    }
}
