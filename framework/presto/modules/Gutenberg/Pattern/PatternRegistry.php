<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\Gutenberg\Pattern;

/**
 * Theme Pattern Registry
 * 
 * Uses Strategy Pattern for persistence based on runtime.
 */
class PatternRegistry
{
    protected ?PatternStorageInterface $storage = null;
    protected array $files = [];
    protected array $fileCache = [];
    protected bool $discovered = false;
    protected bool $wpStubsLoaded = false;
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
        if (isset($this->fileCache[$file])) {
            return $this->fileCache[$file]['slug'] ?? null;
        }

        $content = file_get_contents($file);
        $slug = null;
        if (preg_match('/\*\s*Slug:\s*(.+)/i', $content, $m)) {
            $slug = trim($m[1]);
        } else {
            $base = basename($file, '.php');
            $slug = 'twentytwentyfive/' . $base;
        }

        $this->fileCache[$file] = [
            'slug' => $slug,
            'content' => $content,
        ];

        return $slug;
    }

    protected function renderFile(string $file): string
    {
        $this->ensureWpStubs();

        if (isset($this->fileCache[$file])) {
            $raw = $this->fileCache[$file]['content'];
        } else {
            $raw = file_get_contents($file);
            $this->fileCache[$file] = [
                'slug' => basename($file, '.php'),
                'content' => $raw,
            ];
        }

        // Strip the PHP doc-block header
        $raw = preg_replace('#<\?php\s*/\*.*?\*/\s*\?>\s*#s', '', $raw, 1);
        $raw = preg_replace('#<\?php\s*/\*.*?\*/\s*#s', '', $raw, 1);

        return preg_replace_callback('#<\?php\s*(.+?)\s*\?>#s', function ($m) {
            $code = trim($m[1]);

            $code = preg_replace('/\b__(?=\s*\()/', 'wp_stubs_translate', $code);
            $code = preg_replace('/\b_e(?=\s*\()/', 'wp_stubs_translate_echo', $code);
            $code = preg_replace('/\b_x(?=\s*\()/', 'wp_stubs_translate_context', $code);

            ob_start();
            try {
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

    protected function ensureWpStubs(): void
    {
        if (!$this->wpStubsLoaded) {
            $this->wpStubsLoaded = true;
            require_once __DIR__ . '/wp-stubs.php';
        }
    }
}
