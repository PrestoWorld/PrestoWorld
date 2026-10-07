<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\Gutenberg\Theme;

/**
 * Compiles theme.json into CSS variables
 */
class ThemeJson
{
    protected array $data;
    protected ?string $cachePath = null;

    public function __construct(string $path, ?string $cacheDir = null)
    {
        $file = is_dir($path) ? rtrim($path, '/') . '/theme.json' : $path;
        $this->data = file_exists($file) ? (json_decode(file_get_contents($file), true) ?? []) : [];

        if ($cacheDir !== null) {
            if (is_dir($cacheDir) || @mkdir($cacheDir, 0775, true)) {
                $cacheKey = 'themejson_' . md5($file . (file_exists($file) ? filemtime($file) : '0'));
                $this->cachePath = $cacheDir . '/' . $cacheKey . '.php';
            }
        }
    }

    public function compile(): string
    {
        if ($this->cachePath !== null && file_exists($this->cachePath)) {
            $cached = require $this->cachePath;
            if (is_string($cached)) {
                return $cached;
            }
        }

        $css = ":root {\n";

        $settings = $this->data['settings'] ?? [];

        if (isset($settings['spacing']['spacingSizes'])) {
            foreach ($settings['spacing']['spacingSizes'] as $size) {
                $css .= "  --wp--preset--spacing--{$size['slug']}: {$size['size']};\n";
            }
        }

        $colorStyles = "";
        if (isset($settings['color']['palette'])) {
            foreach ($settings['color']['palette'] as $color) {
                $slug = $color['slug'];
                $val = $color['color'];
                $css .= "  --wp--preset--color--{$slug}: {$val};\n";
                $colorStyles .= ".has-{$slug}-color { color: var(--wp--preset--color--{$slug}) !important; }\n";
                $colorStyles .= ".has-{$slug}-background-color { background-color: var(--wp--preset--color--{$slug}) !important; }\n";
            }
        }

        if (isset($settings['typography']['fontSizes'])) {
            foreach ($settings['typography']['fontSizes'] as $size) {
                $css .= "  --wp--preset--font-size--{$size['slug']}: {$size['size']};\n";
            }
        }

        $contentSize = $settings['layout']['contentSize'] ?? '800px';
        $wideSize = $settings['layout']['wideSize'] ?? '1200px';
        $css .= "  --wp--style--global--content-size: {$contentSize};\n";
        $css .= "  --wp--style--global--wide-size: {$wideSize};\n";
        $css .= "}\n\n";

        $css .= "/* Layout Engine */\n";
        $css .= ".is-layout-constrained > :where(:not(.alignleft):not(.alignright):not(.alignfull)) { \n";
        $css .= "  max-width: var(--wp--style--global--content-size); \n";
        $css .= "  margin-left: auto !important; \n";
        $css .= "  margin-right: auto !important; \n";
        $css .= "}\n";
        $css .= ".is-layout-constrained > .alignwide { max-width: var(--wp--style--global--wide-size); }\n";
        $css .= ".is-layout-flow > :where(:not(.alignleft):not(.alignright):not(.alignfull)) { margin-left: auto; margin-right: auto; }\n";
        $css .= ".has-global-padding { padding-left: var(--wp--preset--spacing--50, 2rem); padding-right: var(--wp--preset--spacing--50, 2rem); }\n";

        $css .= ".is-layout-flex { display: flex; gap: var(--wp--style--block-gap, 1.25rem); }\n";
        $css .= ".is-vertical { flex-direction: column; }\n";
        $css .= ".items-justified-space-between { justify-content: space-between; }\n";
        $css .= ".items-justified-center { justify-content: center; }\n";
        $css .= ".items-justified-right { justify-content: flex-end; }\n";

        $css .= "\n/* Block Specifics */\n";
        $css .= ".wp-block-navigation ul { list-style: none; padding: 0; display: flex; gap: 1.5rem; }\n";
        $css .= ".wp-block-navigation a { text-decoration: none; color: inherit; font-weight: 500; transition: opacity 0.2s; }\n";
        $css .= ".wp-block-navigation a:hover { opacity: 0.7; }\n";
        $css .= ".wp-block-navigation__responsive-container-open { display: none; }\n";

        $css .= ".wp-block-columns { display: flex; gap: 2rem; flex-wrap: wrap; }\n";
        $css .= ".wp-block-column { flex: 1; min-width: 0; }\n";

        $css .= ".wp-block-post-template { list-style: none; padding: 0; display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: var(--wp--preset--spacing--60, 3rem); }\n";
        $css .= ".wp-block-post-featured-image img { width: 100%; height: auto; aspect-ratio: 16/9; object-fit: cover; border-radius: 12px; display: block; transition: transform 0.3s ease; }\n";
        $css .= ".wp-block-post-featured-image a:hover img { transform: scale(1.02); }\n";

        $css .= ".wp-block-post-title { margin-top: 1rem; margin-bottom: 0.5rem; line-height: 1.2; }\n";
        $css .= ".wp-block-post-title a { text-decoration: none; color: inherit; transition: color 0.2s; }\n";
        $css .= ".wp-block-post-title a:hover { color: var(--wp--preset--color--accent-3, #503AA8); }\n";

        $css .= ".wp-block-site-title { font-size: var(--wp--preset--font-size--large, 1.5rem); font-weight: 800; text-transform: uppercase; letter-spacing: -0.02em; }\n";
        $css .= ".wp-block-site-title a { text-decoration: none; color: inherit; }\n";

        $css .= $colorStyles;

        $styles = $this->data['styles'] ?? [];
        if (!empty($styles['color']['background'])) {
            $css .= "body { background-color: " . $this->resolveVar($styles['color']['background']) . "; }\n";
        }
        if (!empty($styles['color']['text'])) {
            $css .= "body { color: " . $this->resolveVar($styles['color']['text']) . "; }\n";
        }

        if ($this->cachePath !== null) {
            file_put_contents($this->cachePath, '<?php return ' . var_export($css, true) . ';' . "\n", LOCK_EX);
        }

        return $css;
    }

    public function getSetting(string $dotPath): mixed
    {
        $keys = explode('.', $dotPath);
        $data = $this->data['settings'] ?? [];
        foreach ($keys as $key) {
            if (!isset($data[$key])) return null;
            $data = $data[$key];
        }
        return $data;
    }

    protected function resolveVar(string $value): string
    {
        return preg_replace_callback('#var:([^|]+)\|([^|]+)\|([^|]+)#', function ($m) {
            return "var(--wp--preset--{$m[2]}--{$m[3]})";
        }, $value);
    }
}
