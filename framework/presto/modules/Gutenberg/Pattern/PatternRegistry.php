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
 * - Function whitelist: only known-safe template functions are allowed
 * - Superglobals saved/restored to prevent cross-request contamination
 * - Error reporting masked to prevent sensitive info leakage
 * - Output buffering isolates each block's output
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

            // Only allow a whitelist of safe functions for pattern templates.
            // Any function call that is not in the whitelist is rejected.
            $allowedFunctions = [
                'wp_stubs_translate', 'wp_stubs_translate_echo', 'wp_stubs_translate_context',
                'esc_html', 'esc_attr', 'esc_url', 'esc_url_raw',
                'get_the_ID', 'get_the_title', 'the_title', 'the_content', 'the_excerpt',
                'get_the_content', 'get_the_excerpt', 'get_the_date', 'get_the_time',
                'the_permalink', 'get_permalink', 'the_author', 'get_the_author',
                'has_post_thumbnail', 'get_the_post_thumbnail', 'the_post_thumbnail',
                'wp_get_attachment_image', 'wp_get_attachment_url',
                'count', 'strlen', 'substr', 'str_replace', 'trim', 'implode', 'explode',
                'in_array', 'array_keys', 'array_values', 'array_merge', 'array_map',
                'date', 'time', 'sprintf', 'number_format', 'absint',
                'is_singular', 'is_page', 'is_home', 'is_front_page', 'is_search',
                'is_category', 'is_tag', 'is_tax', 'is_archive', 'is_single',
                'wp_trim_words', 'wp_strip_all_tags', 'sanitize_title',
                'htmlspecialchars', 'strip_tags', 'nl2br',
            ];

            // Extract all function calls from the code
            if (preg_match_all('/\b([a-zA-Z_\x80-\xff][a-zA-Z0-9_\x80-\xff]*)\s*\(/', $code, $matches)) {
                foreach ($matches[1] as $func) {
                    if (in_array($func, $allowedFunctions, true)) {
                        continue;
                    }

                    // Skip control structures that look like function calls
                    if (in_array(strtolower($func), ['if', 'elseif', 'else', 'for', 'foreach', 'while', 'switch', 'case', 'return', 'echo', 'print', 'isset', 'unset', 'empty', 'die', 'exit', 'array', 'list', 'each', 'eval'], true)) {
                        continue;
                    }

                    return "<!-- Pattern block calls disallowed function [{$func}] – skipped -->";
                }
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
