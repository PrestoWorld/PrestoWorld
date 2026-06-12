<?php

declare(strict_types=1);

namespace App\Http;

use App\Contracts\Http\PageRenderer as PageRendererContract;
use App\Contracts\Services\HtmlComposer;

class PageRenderer implements PageRendererContract
{
    /** @var string[] */
    private array $styles = [];

    public function __construct(
        private HtmlComposer $composer,
    ) {}

    public function addStyle(string $css): void
    {
        $this->styles[] = $css;
    }

    public function render(string $body, ?string $title = null): string
    {
        return $this->composer->compose(
            body: $body,
            styles: implode("\n", $this->styles),
            title: $title,
        );
    }
}
