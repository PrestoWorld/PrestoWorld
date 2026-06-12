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

    /**
     * Render a pattern file, executing embedded PHP blocks.
     *
     * SECURITY: This method uses eval() to execute PHP code found inside
     * pattern template files. Only pattern files shipped with the theme
     * should be rendered through this path. Never pass user-uploaded or
     * user-controlled file paths to this method.
     *
     * The following mitigations are applied per block:
     * - Superglobals saved/restored to prevent cross-request contamination
     * - Error reporting masked to prevent sensitive info leakage
     * - Output buffering isolates each block's output
     * - A blacklist of dangerous functions is disabled during eval
     */
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

            if (!str_ends_with($code, ';') && !str_ends_with($code, '}')) {
                $code .= ';';
            }

            // Save and sanitise the execution environment
            $saved = [
                '_SERVER' => $_SERVER,
                '_ENV' => $_ENV,
                '_GET' => $_GET,
                '_POST' => $_POST,
                '_COOKIE' => $_COOKIE,
               '_REQUEST' => $_REQUEST,
                '_FILES' => $_FILES,
                'error_reporting' => error_reporting(),
            ];

            error_reporting(0);

            // Reject code calling functions never needed in pattern
            // templates that also pose a security risk (defence in depth).
            if (preg_match(
                '/\b(?:exec|system|passthru|shell_exec|popen|proc_open|' .
                'pcntl_exec|assert|create_function|include(?:_once)?|' .
                'require(?:_once)?|file_put_contents|unlink|rename|' .
                'rmdir|mkdir|chmod|chown|touch|fopen|fwrite|base64_decode)\s*\(/i',
                $code
            )) {
                return "<!-- Pattern block contains unsafe code – skipped -->";
            }

            ob_start();
            try {
                eval($code);
            } catch (\Throwable $e) {
                ob_end_clean();
                return "<!-- Error rendering PHP block: " . htmlspecialchars($e->getMessage(), ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8') . " -->";
            }
            $output = ob_get_clean();

            // Restore execution environment
            $_SERVER = $saved['_SERVER'];
            $_ENV = $saved['_ENV'];
            $_GET = $saved['_GET'];
            $_POST = $saved['_POST'];
            $_COOKIE = $saved['_COOKIE'];
            $_REQUEST = $saved['_REQUEST'];
            $_FILES = $saved['_FILES'];
            error_reporting($saved['error_reporting']);

            return $output;
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
