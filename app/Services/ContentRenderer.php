<?php

declare(strict_types=1);

namespace App\Services;

use PrestoWorld\Modules\Gutenberg\Module as GutenbergModule;
use App\Exceptions\RenderException;

class ContentRenderer
{
    public function __construct(
        private ?GutenbergModule $gutenberg = null,
    ) {}

    public function render(string $template): string
    {
        if ($this->gutenberg === null) {
            throw new RenderException('Gutenberg module is not available');
        }

        $result = $this->gutenberg->renderTemplate($template);

        if ($result === null || $result === '') {
            throw new RenderException("Template [{$template}] returned empty content");
        }

        return $result;
    }

    public function getStyles(): string
    {
        return $this->gutenberg?->getStyles() ?? '';
    }
}
