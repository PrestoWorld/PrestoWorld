<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\Gutenberg\Theme;

use PrestoWorld\Modules\Gutenberg\Pattern\PatternCompiler;
use PrestoWorld\Modules\Gutenberg\Pattern\PatternStorageInterface;

/**
 * Template Part Registry & Compiler
 */
class TemplatePartRegistry
{
    protected string $partsPath;
    protected ?PatternCompiler $compiler = null;
    protected ?PatternStorageInterface $storage = null;
    protected array $cache = [];

    public function __construct(string $themePath, ?string $cachePath = null)
    {
        $this->partsPath = rtrim($themePath, '/') . '/parts';
        if ($cachePath !== null) {
            $this->compiler = new PatternCompiler($cachePath);
        }
    }

    public function setStorage(PatternStorageInterface $storage): void
    {
        $this->storage = $storage;
    }

    public function get(string $slug): ?string
    {
        if (isset($this->cache[$slug])) {
            return $this->cache[$slug];
        }

        $cached = $this->storage?->get("part:{$slug}");
        if ($cached !== null) {
            return $this->cache[$slug] = $cached;
        }

        $path = $this->partsPath . "/{$slug}.php";
        if (!file_exists($path)) {
            $path = $this->partsPath . "/{$slug}.html";
        }

        if (!file_exists($path)) {
            return null;
        }

        $content = $this->renderFile($path);
        $this->storage?->set("part:{$slug}", $content);

        return $this->cache[$slug] = $content;
    }

    protected function renderFile(string $file): string
    {
        if (str_ends_with($file, '.html')) {
            return file_get_contents($file);
        }

        if ($this->compiler === null) {
             ob_start();
             include $file;
             return ob_get_clean();
        }

        if ($this->compiler->isExpired($file)) {
            $this->compiler->compile($file);
        }

        $cachedFile = $this->compiler->getCached($file);

        // Sanitize environment
        $saved = $_SERVER;
        error_reporting(0);
        
        ob_start();
        try {
            include $cachedFile;
        } catch (\Throwable $e) {
            ob_end_clean();
            return "<!-- Error rendering part: " . htmlspecialchars($e->getMessage()) . " -->";
        }
        $output = ob_get_clean();
        
        $_SERVER = $saved;
        error_reporting(E_ALL);

        return $output;
    }
}
