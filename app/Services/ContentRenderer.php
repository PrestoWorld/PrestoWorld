<?php

declare(strict_types=1);

namespace App\Services;

use PrestoWorld\Modules\ClassicTheme\ClassicThemeEngine;
use PrestoWorld\Modules\ClassicTheme\ThemeDetector;
use PrestoWorld\Modules\ContextBuilder\ContextLoader;
use App\Contracts\Services\ContentRenderer as ContentRendererContract;
use App\Contracts\Services\RenderedContent;
use Witals\Framework\Contracts\Container;

class ContentRenderer implements ContentRendererContract
{
    private ?ClassicThemeEngine $classicEngine = null;

    private bool $resolved = false;

    public function __construct(
        private ContextLoader $contextLoader,
        private Container $container,
        private string $themePath = '',
    ) {}

    public function render(string $template, array $post = []): RenderedContent
    {
        $classic = $this->classicEngine();
        if ($classic !== null) {
            return $classic->render($template, $post);
        }

        return RenderedContent::complete($this->contextLoader->renderTemplate($template, $post));
    }

    public function supports(string $template): bool
    {
        $classic = $this->classicEngine();
        if ($classic !== null) {
            return $classic->supports($template);
        }

        return $this->contextLoader->supports($template);
    }

    /**
     * Resolve the classic engine only when the active theme is a legacy
     * (pre-Gutenberg, PHP-template) WordPress theme. New/block themes are
     * rendered by the ContextBuilder ContextLoader (the primary template engine).
     */
    private function classicEngine(): ?ClassicThemeEngine
    {
        if ($this->resolved) {
            return $this->classicEngine;
        }

        $this->resolved = true;

        if ($this->themePath === '' || !ThemeDetector::isClassic($this->themePath)) {
            return null;
        }

        if ($this->container->has(ClassicThemeEngine::class)) {
            $engine = $this->container->make(ClassicThemeEngine::class);
            if ($engine instanceof ClassicThemeEngine) {
                $this->classicEngine = $engine;
            }
        }

        return $this->classicEngine;
    }
}
