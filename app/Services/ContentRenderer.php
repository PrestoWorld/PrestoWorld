<?php

declare(strict_types=1);

namespace App\Services;

use PrestoWorld\Modules\Gutenberg\Module as GutenbergModule;
use App\Contracts\Services\ContentRenderer as ContentRendererContract;
use App\Exceptions\RenderException;

class ContentRenderer implements ContentRendererContract
{
    public function __construct(
        private GutenbergModule $gutenberg,
    ) {}

    public function render(string $template): string
    {
        $result = $this->gutenberg->renderTemplate($template);

        if ($result === null || $result === '') {
            throw new RenderException("Template [{$template}] returned empty content");
        }

        return $result;
    }

    public function getStyles(): string
    {
        return $this->gutenberg->getStyles();
    }
}
