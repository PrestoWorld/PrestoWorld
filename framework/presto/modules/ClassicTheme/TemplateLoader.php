<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\ClassicTheme;

class TemplateLoader
{
    private string $themePath;

    private FunctionsLoader $functionsLoader;

    private TemplateHierarchy $hierarchy;

    private array $templateCache = [];

    public function __construct(
        string $themePath,
        FunctionsLoader $functionsLoader,
        TemplateHierarchy $hierarchy,
    ) {
        $this->themePath = rtrim($themePath, '/');
        $this->functionsLoader = $functionsLoader;
        $this->hierarchy = $hierarchy;
    }

    public function render(string $template, array $post = []): string
    {
        $this->functionsLoader->load();

        $candidates = $this->hierarchy->resolve($template, $post);
        $templateFile = $this->findFirst($candidates);

        if ($templateFile === null) {
            return '';
        }

        ob_start();

        try {
            $this->includeTemplate($templateFile, $post);
        } catch (\Throwable) {
            // Template may depend on WordPress not being available
        }

        return ob_get_clean();
    }

    public function renderFull(string $template, array $post = []): string
    {
        $this->functionsLoader->load();

        $candidates = $this->hierarchy->resolve($template, $post);
        $templateFile = $this->findFirst($candidates);

        ob_start();

        try {
            if ($templateFile !== null) {
                $this->includeTemplate($templateFile, $post);
            } else {
                echo '<!-- No template found -->';
            }
        } catch (\Throwable) {
            // Template may depend on WordPress not being available
        }

        return ob_get_clean();
    }

    public function renderPart(string $part, array $post = []): string
    {
        $this->functionsLoader->load();

        $partFile = $this->themePath . '/template-parts/' . $part . '.php';

        if (!file_exists($partFile)) {
            return '';
        }

        ob_start();

        try {
            $this->includeTemplate($partFile, $post, 'template-part');
        } catch (\Throwable) {
            // Template part may depend on WordPress not being available
        }

        return ob_get_clean();
    }

    private function findFirst(array $candidates): ?string
    {
        foreach ($candidates as $candidate) {
            $path = $this->themePath . '/' . $candidate . '.php';

            if (file_exists($path)) {
                return $path;
            }

            if ($candidate === 'index') {
                $path = $this->themePath . '/index.php';
                if (file_exists($path)) {
                    return $path;
                }
            }
        }

        return null;
    }

    private function includeTemplate(string $path, array $post, ?string $type = null): void
    {
        $themePath = $this->themePath;

        (static function () use ($path, $post, $themePath) {
            $GLOBALS['post'] = $post;
            extract($post, EXTR_SKIP);
            require $path;
        })();
    }
}
