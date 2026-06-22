<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\ClassicTheme;

use PrestoWorld\Contracts\Theme\ThemeEngineInterface;
use App\Contracts\Services\RenderedContent;
use Witals\Framework\Contracts\Container;

class ClassicThemeEngine implements ThemeEngineInterface
{
    private string $themePath;

    private Container $container;

    private ?StyleParser $styleParser = null;

    private ?TemplateLoader $templateLoader = null;

    private ?FunctionsLoader $functionsLoader = null;

    private ?TemplateHierarchy $hierarchy = null;

    public function __construct(
        string $themePath,
        Container $container,
    ) {
        $this->themePath = rtrim($themePath, '/');
        $this->container = $container;
    }

    public function render(string $template, array $post = []): RenderedContent
    {
        $loader = $this->getTemplateLoader();

        try {
            $hasHeader = file_exists($this->themePath . '/header.php');
            $hasFooter = file_exists($this->themePath . '/footer.php');

            if ($hasHeader && $hasFooter) {
                $body = $loader->renderFull($template, $post);
            } else {
                $body = $loader->render($template, $post);
            }
        } catch (\Throwable $e) {
            $body = sprintf(
                '<!-- ClassicThemeEngine: template "%s" could not be rendered (%s) -->',
                $template,
                $e->getMessage(),
            );
        }

        $styles = $this->buildInlineStyles();

        return RenderedContent::complete($body, $styles);
    }

    public function getStyles(): string
    {
        return $this->buildInlineStyles();
    }

    public function supports(string $template): bool
    {
        $hierarchy = $this->getHierarchy();
        $candidates = $hierarchy->resolve($template);

        foreach ($candidates as $candidate) {
            $path = $this->themePath . '/' . $candidate . '.php';
            if (file_exists($path)) {
                return true;
            }
        }

        return false;
    }

    public function getThemePath(): string
    {
        return $this->themePath;
    }

    public function getStyleParser(): StyleParser
    {
        if ($this->styleParser === null) {
            $this->styleParser = new StyleParser($this->themePath . '/style.css');
        }

        return $this->styleParser;
    }

    public function getTemplateLoader(): TemplateLoader
    {
        if ($this->templateLoader === null) {
            $this->templateLoader = new TemplateLoader(
                $this->themePath,
                $this->getFunctionsLoader(),
                $this->getHierarchy(),
            );
        }

        return $this->templateLoader;
    }

    public function getFunctionsLoader(): FunctionsLoader
    {
        if ($this->functionsLoader === null) {
            $this->functionsLoader = new FunctionsLoader($this->themePath);
        }

        return $this->functionsLoader;
    }

    public function getHierarchy(): TemplateHierarchy
    {
        if ($this->hierarchy === null) {
            $this->hierarchy = new TemplateHierarchy();
        }

        return $this->hierarchy;
    }

    public function renderPart(string $part, array $post = []): string
    {
        return $this->getTemplateLoader()->renderPart($part, $post);
    }

    private function buildInlineStyles(): string
    {
        $parser = $this->getStyleParser();
        $css = $parser->css();

        if ($css === '') {
            return '';
        }

        return $css;
    }
}
