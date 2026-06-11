<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\Gutenberg\Pattern;

require_once __DIR__ . '/wp-stubs.php';

/**
 * Theme Pattern Registry
 * 
 * Uses Strategy Pattern for persistence based on runtime.
 */
class PatternRegistry
{
    protected ?PatternStorageInterface $storage = null;
    protected array $files = []; // Internal map of slug => original file path
    protected bool $discovered = false;
    protected string $patternsPath;

    public function __construct(string $themePath)
    {
        $this->patternsPath = rtrim($themePath, '/') . '/patterns';
    }

    public function setStorage(PatternStorageInterface $storage): void
    {
        $this->storage = $storage;
    }

    public function discover(): void
    {
        if ($this->discovered) return;
        $this->discovered = true;

        if (!is_dir($this->patternsPath)) return;

        foreach (glob($this->patternsPath . '/*.php') as $file) {
            $slug = $this->extractSlug($file);
            if ($slug !== null) {
                $this->files[$slug] = $file;
                
                // If storage doesn't have it (cold start in FPM), we'll render it on first get
                // If in RR, the boot warmup will call get() on all to pre-fill RAM
            }
        }
    }

    public function get(string $slug): ?string
    {
        $this->discover();

        if (!isset($this->files[$slug])) return null;

        // Try storage (Memory or FileCache)
        $cached = $this->storage?->get($slug);
        if ($cached !== null) return $cached;

        // Cold start: render, store, and return
        $content = $this->renderFile($this->files[$slug]);
        $this->storage?->set($slug, $content);

        return $content;
    }

    protected function extractSlug(string $file): ?string
    {
        $content = file_get_contents($file, false, null, 0, 512);
        if (preg_match('/\*\s*Slug:\s*(.+)/i', $content, $m)) {
            return trim($m[1]);
        }
        $base = basename($file, '.php');
        return 'twentytwentyfive/' . $base;
    }

    protected function renderFile(string $file): string
    {
        $raw = file_get_contents($file);
        // Strip the PHP doc-block header
        $raw = preg_replace('#<\?php\s*/\*.*?\*/\s*\?>\s*#s', '', $raw, 1);

        // Resolve common WP i18n inline PHP calls
        return preg_replace_callback('#<\?php\s*(.+?)\s*;\s*\?>#s', function ($m) {
            return $this->resolvePhpExpression(trim($m[1]));
        }, $raw);
    }

    protected function resolvePhpExpression(string $expr): string
    {
        if (preg_match("#^esc_html_e\s*\(\s*['\"](.+?)['\"]\s*(?:,\s*['\"][^'\"]*['\"]\s*)?\)#s", $expr, $m)) {
            return htmlspecialchars($m[1], ENT_QUOTES, 'UTF-8');
        }
        if (preg_match("#^esc_html__\s*\(\s*['\"](.+?)['\"]\s*(?:,\s*['\"][^'\"]*['\"]\s*)?\)#s", $expr, $m)) {
            return htmlspecialchars($m[1], ENT_QUOTES, 'UTF-8');
        }
        if (preg_match("#^esc_html_x\s*\(\s*['\"](.+?)['\"]#s", $expr, $m)) {
            return htmlspecialchars($m[1], ENT_QUOTES, 'UTF-8');
        }
        if (preg_match("#^printf\s*\(\s*esc_html__\s*\(\s*['\"](.+?)['\"]#s", $expr, $m)) {
            $text = htmlspecialchars($m[1], ENT_QUOTES, 'UTF-8');
            return str_replace('%s', '<a href="https://wordpress.org" rel="nofollow">WordPress</a>', $text);
        }
        return '';
    }
}
