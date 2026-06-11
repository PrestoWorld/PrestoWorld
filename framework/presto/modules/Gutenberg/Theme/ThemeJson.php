<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\Gutenberg\Theme;

/**
 * High-Performance theme.json Compiler
 * 
 * Optimized for performance by using a compilation approach. 
 * This class focuses on extracting and formatting theme data into 
 * static-friendly structures that can be easily cached.
 */
class ThemeJson
{
    protected array $settings = [];
    protected array $styles = [];
    protected string $themePath;
    protected ?string $compiledCss = null;

    public function __construct(string $themePath)
    {
        $this->themePath = rtrim($themePath, '/');
        $this->load();
    }

    /**
     * Load and decode theme.json
     */
    protected function load(): void
    {
        $filePath = $this->themePath . '/theme.json';
        if (!file_exists($filePath)) {
            return;
        }

        $data = json_decode(file_get_contents($filePath), true) ?: [];
        $this->settings = $data['settings'] ?? [];
        $this->styles = $data['styles'] ?? [];
    }

    /**
     * Compile theme.json to CSS with a fokus on performance
     */
    public function compile(): string
    {
        if ($this->compiledCss !== null) {
            return $this->compiledCss;
        }

        $sections = [
            $this->compileFontFaces(),
            ":root {\n" . $this->compilePresets() . $this->compileLayout() . "}\n",
            "body {\n" . $this->compileGlobalStyles() . "}\n"
        ];

        $this->compiledCss = implode("\n", array_filter($sections));
        return $this->compiledCss;
    }

    protected function compilePresets(): string
    {
        $buffer = "";
        
        // 1. Colors
        foreach ($this->settings['color']['palette'] ?? [] as $color) {
            $buffer .= "  --wp--preset--color--{$color['slug']}: {$color['color']};\n";
        }

        // 2. Typography
        foreach ($this->settings['typography']['fontSizes'] ?? [] as $size) {
            $value = is_array($size['size']) ? ($size['size']['min'] ?? $size['size']['max']) : $size['size'];
            $buffer .= "  --wp--preset--font-size--{$size['slug']}: {$value};\n";
        }

        foreach ($this->settings['typography']['fontFamilies'] ?? [] as $family) {
            $buffer .= "  --wp--preset--font-family--{$family['slug']}: {$family['fontFamily']};\n";
        }

        // 3. Spacing
        foreach ($this->settings['spacing']['spacingSizes'] ?? [] as $spacing) {
            $buffer .= "  --wp--preset--spacing--{$spacing['slug']}: {$spacing['size']};\n";
        }

        return $buffer;
    }

    protected function compileFontFaces(): string
    {
        $buffer = "";
        foreach ($this->settings['typography']['fontFamilies'] ?? [] as $family) {
            foreach ($family['fontFace'] ?? [] as $face) {
                $srcs = $this->resolveFontFaceSrc($face['src'] ?? []);
                $buffer .= "@font-face{font-family:'{$face['fontFamily']}';font-weight:{$face['fontWeight']};font-style:{$face['fontStyle']};font-display:swap;src:" . implode(',', $srcs) . ";}\n";
            }
        }
        return $buffer;
    }

    protected function resolveFontFaceSrc(array $srcs): array
    {
        return array_map(function($src) {
            $cleanSrc = str_replace(['file:./', './'], '', $src);
            // Reference theme assets correctly
            return "url('/content/themes/twentytwentyfive/{$cleanSrc}') format('woff2')";
        }, $srcs);
    }

    protected function compileLayout(): string
    {
        $layout = $this->settings['layout'] ?? [];
        $buffer = "";
        if (isset($layout['contentSize'])) {
            $buffer .= "  --wp--style--global--content-size: {$layout['contentSize']};\n";
        }
        if (isset($layout['wideSize'])) {
            $buffer .= "  --wp--style--global--wide-size: {$layout['wideSize']};\n";
        }
        return $buffer;
    }

    protected function compileGlobalStyles(): string
    {
        $buffer = "";
        $color = $this->styles['color'] ?? [];
        $typo = $this->styles['typography'] ?? [];

        if (isset($color['background'])) $buffer .= "  background-color: " . $this->resolveRef($color['background']) . ";\n";
        if (isset($color['text']))       $buffer .= "  color: " . $this->resolveRef($color['text']) . ";\n";
        if (isset($typo['fontFamily']))  $buffer .= "  font-family: " . $this->resolveRef($typo['fontFamily']) . ";\n";
        if (isset($typo['fontSize']))    $buffer .= "  font-size: " . $this->resolveRef($typo['fontSize']) . ";\n";
        if (isset($typo['lineHeight']))  $buffer .= "  line-height: {$typo['lineHeight']};\n";

        return $buffer;
    }

    protected function resolveRef(string $value): string
    {
        if (str_starts_with($value, 'var:')) {
            return "var(--wp--" . str_replace(['var:', '|'], ['', '--'], $value) . ")";
        }
        return $value;
    }

    /**
     * Get specific setting by key
     */
    public function getSetting(string $key, $default = null)
    {
        $keys = explode('.', $key);
        $data = $this->settings;
        foreach ($keys as $k) {
            if (!isset($data[$k])) return $default;
            $data = $data[$k];
        }
        return $data;
    }
}
