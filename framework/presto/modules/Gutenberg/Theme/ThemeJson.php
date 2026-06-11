<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\Gutenberg\Theme;

/**
 * Compiles theme.json into CSS variables
 */
class ThemeJson
{
    protected array $data;

    public function __construct(string $path)
    {
        $this->data = file_exists($path) ? json_decode(file_get_contents($path), true) : [];
    }

    public function compile(): string
    {
        $css = ":root {\n";
        
        $settings = $this->data['settings'] ?? [];

        // 1. Spacing
        if (isset($settings['spacing']['spacingSizes'])) {
            foreach ($settings['spacing']['spacingSizes'] as $size) {
                $css .= "  --wp--preset--spacing--{$size['slug']}: {$size['size']};\n";
            }
        }

        // 2. Colors
        if (isset($settings['color']['palette'])) {
            foreach ($settings['color']['palette'] as $color) {
                $css .= "  --wp--preset--color--{$color['slug']}: {$color['color']};\n";
            }
        }

        // 3. Typography
        if (isset($settings['typography']['fontSizes'])) {
            foreach ($settings['typography']['fontSizes'] as $size) {
                $css .= "  --wp--preset--font-size--{$size['slug']}: {$size['size']};\n";
            }
        }

        // 4. Layout Sizes
        if (isset($settings['layout'])) {
            $contentSize = $settings['layout']['contentSize'] ?? '800px';
            $wideSize = $settings['layout']['wideSize'] ?? '1200px';
            $css .= "  --wp--style--global--content-size: {$contentSize};\n";
            $css .= "  --wp--style--global--wide-size: {$wideSize};\n";
        }

        $css .= "}\n";
        return $css;
    }
}
