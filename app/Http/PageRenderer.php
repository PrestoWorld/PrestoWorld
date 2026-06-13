<?php

declare(strict_types=1);

namespace App\Http;

use App\Contracts\Http\PageRenderer as PageRendererContract;
use App\Contracts\Services\HtmlComposer;
use App\Contracts\Services\RenderedContent;

class PageRenderer implements PageRendererContract
{
    public function __construct(
        private HtmlComposer $composer,
    ) {}

    public function render(RenderedContent $content, ?string $title = null): string
    {
        return $this->composer->compose(
            body: $content->body,
            styles: $content->styles,
            title: $title,
        );
    }
}
