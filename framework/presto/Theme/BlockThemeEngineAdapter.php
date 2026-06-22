<?php

declare(strict_types=1);

namespace PrestoWorld\Theme;

use PrestoWorld\Contracts\Theme\ThemeEngineInterface;
use PrestoWorld\Modules\Gutenberg\Module as GutenbergModule;
use App\Contracts\Services\RenderedContent;

class BlockThemeEngineAdapter implements ThemeEngineInterface
{
    public function __construct(
        private GutenbergModule $gutenberg,
    ) {}

    public function render(string $template, array $post = []): RenderedContent
    {
        return new RenderedContent(
            body: $this->gutenberg->renderTemplate($template) ?? '',
            styles: $this->gutenberg->getStyles(),
        );
    }

    public function getStyles(): string
    {
        return $this->gutenberg->getStyles();
    }

    public function supports(string $template): bool
    {
        $themePath = $this->getThemePath();
        return file_exists($themePath . '/templates/' . $template . '.html');
    }

    private function getThemePath(): string
    {
        return getenv('PW_THEME_DIR') ?: '';
    }
}
