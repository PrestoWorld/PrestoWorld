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

        // We use output buffering to capture the result of PHP execution
        // While wp-stubs.php is already required in the constructor,
        // we wrap each block's execution to return its output.
        return preg_replace_callback('#<\?php\s*(.+?)\s*\?>#s', function ($m) {
            $code = trim($m[1]);

            // WordPress compatibility: rename conflicting global functions to avoid
            // clashes with framework helpers (like Witals' __ helper).
            $code = preg_replace('/\b__(?=\s*\()/', 'wp_stubs_translate', $code);
            $code = preg_replace('/\b_e(?=\s*\()/', 'wp_stubs_translate_echo', $code);
            $code = preg_replace('/\b_x(?=\s*\()/', 'wp_stubs_translate_context', $code);

            // If it's a simple return/expression, try to return it.
            // If it's a statement with echo/printf, capture it.
            ob_start();
            try {
                // Ensure the code ends with a semicolon if it's not a block
                if (!str_ends_with($code, ';') && !str_ends_with($code, '}')) {
                    $code .= ';';
                }
                eval($code);
            } catch (\Throwable $e) {
                ob_end_clean();
                return "<!-- Error rendering PHP block: " . htmlspecialchars($e->getMessage()) . " -->";
            }
            return ob_get_clean();
        }, $raw);
    }
}
