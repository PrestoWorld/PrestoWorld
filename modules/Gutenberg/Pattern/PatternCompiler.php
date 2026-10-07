<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\Gutenberg\Pattern;

/**
 * Compiles pattern template PHP blocks into cache files,
 * replacing eval() with safe include().
 *
 * Each pattern file is compiled once into a plain PHP file under
 * a writable cache directory. The compiled file is a direct concatenation
 * of literal HTML and <?php blocks, just like a normal PHP template.
 */
class PatternCompiler
{
    private const ALLOWED_FUNCTIONS = [
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
        'printf', 'esc_html__', 'esc_html_e', 'esc_html_x', 'esc_attr__', 'esc_attr_e',
        '_n', 'wp_kses_post', 'get_theme_file_uri', 'get_template_directory_uri', 'site_url',
    ];

    private const CONTROL_STRUCTURES = [
        'if', 'elseif', 'else', 'for', 'foreach', 'while', 'switch',
        'case', 'return', 'echo', 'print', 'isset', 'unset', 'empty',
        'die', 'exit', 'array', 'list', 'each', 'eval',
    ];

    public function __construct(
        private string $cachePath,
    ) {}

    public function compile(string $file): string
    {
        $raw = file_get_contents($file);
        $cacheFile = $this->getCachePath($file);

        // Strip the PHP doc-block header
        $raw = preg_replace('#<\?php\s*/\*.*?\*/\s*\?>\s*#s', '', $raw, 1);
        $raw = preg_replace('#<\?php\s*/\*.*?\*/\s*#s', '', $raw, 1);

        // Process all PHP blocks using a Lexer for safety and accuracy
        $raw = preg_replace_callback('#<\?php\s*(.+?)\s*\?>#s', function ($m) {
            return $this->processPhpBlock($m[1]);
        }, $raw);

        $dir = dirname($cacheFile);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents($cacheFile, $raw, LOCK_EX);

        return $cacheFile;
    }

    public function isExpired(string $file): bool
    {
        $cacheFile = $this->getCachePath($file);

        if (!file_exists($cacheFile)) {
            return true;
        }

        return filemtime($file) > filemtime($cacheFile);
    }

    public function getCached(string $file): string
    {
        return $this->getCachePath($file);
    }

    private function getCachePath(string $file): string
    {
        $hash = md5($file);
        $basename = basename($file, '.php');

        return rtrim($this->cachePath, '/') . '/' . $basename . '_' . $hash . '.php';
    }

    /**
     * Safely process a PHP block using token_get_all()
     */
    private function processPhpBlock(string $code): string
    {
        $tokens = token_get_all('<?php ' . trim($code) . ' ?>');
        $processed = '';
        $replaces = [
            '__' => 'wp_stubs_translate',
            '_e' => 'wp_stubs_translate_echo',
            '_x' => 'wp_stubs_translate_context',
        ];

        foreach ($tokens as $i => $token) {
            if (is_array($token)) {
                [$id, $text] = $token;

                if ($id === T_OPEN_TAG || $id === T_CLOSE_TAG) {
                    continue;
                }

                if ($id === T_STRING) {
                    // Check if it's one of our translation functions
                    if (isset($replaces[$text])) {
                        $processed .= $replaces[$text];
                        continue;
                    }

                    // For any string, if it's followed by '(', it's likely a function call.
                    // We must validate it against our whitelist.
                    $next = $this->getNextNonWhitespace($tokens, $i);
                    if ($next === '(') {
                        if (!in_array($text, self::ALLOWED_FUNCTIONS, true) &&
                            !in_array(strtolower($text), self::CONTROL_STRUCTURES, true)) {
                            return '<?php /* Disallowed function: ' . $text . ' */ ?>';
                        }
                    }
                }

                $processed .= $text;
            } else {
                $processed .= $token;
            }
        }

        $result = trim($processed);
        if ($result !== '' && !str_ends_with($result, ';') && !str_ends_with($result, '}')) {
            $result .= ';';
        }

        return '<?php ' . $result . ' ?>';
    }

    private function getNextNonWhitespace(array $tokens, int $currentIndex): ?string
    {
        for ($i = $currentIndex + 1; $i < count($tokens); $i++) {
            $token = $tokens[$i];
            if (is_array($token)) {
                if ($token[0] === T_WHITESPACE) {
                    continue;
                }
                return $token[1];
            }
            return $token;
        }
        return null;
    }
}
