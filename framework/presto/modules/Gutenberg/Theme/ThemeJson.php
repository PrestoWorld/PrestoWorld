<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\Gutenberg\Theme;

/**
 * High-performance theme.json Parser
 * 
 * Extracts styles, settings, and custom templates/patterns 
 * from theme.json files used in WordPress Block Themes.
 */
class ThemeJson
{
    protected array $data = [];

    public function __construct(string $filePath)
    {
        if (file_exists($filePath)) {
            $this->data = json_decode(file_get_contents($filePath), true) ?: [];
        }
    }

    /**
     * Get settings for a specific block or global settings
     */
    public function getSettings(?string $blockName = null): array
    {
        $settings = $this->data['settings'] ?? [];
        if ($blockName && isset($settings['blocks'][$blockName])) {
            return array_merge($settings, $settings['blocks'][$blockName]);
        }
        return $settings;
    }

    /**
     * Get CSS Custom Properties (Variables) generated from theme.json settings
     */
    public function generateCssVariables(): string
    {
        $css = ":root {\n";
        $settings = $this->data['settings'] ?? [];

        // Colors
        if (isset($settings['color']['palette'])) {
            foreach ($settings['color']['palette'] as $color) {
                $css .= "  --wp--preset--color--{$color['slug']}: {$color['color']};\n";
            }
        }

        // Gradients
        if (isset($settings['color']['gradients'])) {
            foreach ($settings['color']['gradients'] as $gradient) {
                $css .= "  --wp--preset--gradient--{$gradient['slug']}: {$gradient['gradient']};\n";
            }
        }

        // Typography - Font Sizes
        if (isset($settings['typography']['fontSizes'])) {
            foreach ($settings['typography']['fontSizes'] as $size) {
                $css .= "  --wp--preset--font-size--{$size['slug']}: {$size['size']};\n";
            }
        }

        // Spacing
        if (isset($settings['spacing']['spacingScale'])) {
            // Simplified spacing scale logic
        }

        $css .= "}\n";
        return $css;
    }

    /**
     * Get raw data
     */
    public function getData(): array
    {
        return $this->data;
    }
}
