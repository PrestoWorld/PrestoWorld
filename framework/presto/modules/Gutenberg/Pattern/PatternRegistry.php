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
            }
        }
    }

    public function get(string $slug): ?string
    {
        $this->discover();

        if (!isset($this->files[$slug])) return null;

        $cached = $this->storage?->get($slug);
        if ($cached !== null) return $cached;

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
        $raw = preg_replace('#<\?php\s*/\*.*?\*/\s*#s', '', $raw, 1);

        // Resolve PHP blocks
        return preg_replace_callback('#<\?php\s*(.+?)\s*\?>#s', function ($m) {
            return $this->resolvePhpExpression(trim($m[1], " \n\r\t;"));
        }, $raw);
    }

    protected function resolvePhpExpression(string $expr): string
    {
        // Handle translate functions
        if (preg_match("#(?:esc_html_e|esc_html__|esc_html_x)\s*\(\s*['\"](.+?)['\"]#s", $expr, $m)) {
            return htmlspecialchars($m[1], ENT_QUOTES, 'UTF-8');
        }
        
        // Handle printf with Designed with WordPress
        if (preg_match("#printf\s*\(\s*esc_html__\s*\(\s*['\"]Designed with %s['\"]#s", $expr)) {
            return 'Designed with <a href="https://wordpress.org" rel="nofollow">WordPress</a>';
        }

        // Handle Site Title/URL
        if (str_contains($expr, 'get_bloginfo')) return 'PrestoWorld';
        if (str_contains($expr, 'home_url')) return '/';

        return '';
    }
}
