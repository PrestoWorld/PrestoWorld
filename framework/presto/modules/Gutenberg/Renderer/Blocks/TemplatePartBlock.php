<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\Gutenberg\Renderer\Blocks;

/**
 * Template Part Block rendering core/template-part
 */
class TemplatePartBlock extends AbstractBlock
{
    public function render(array $context): string
    {
        $slug = $this->attrs['slug'] ?? '';
        $area = $this->attrs['area'] ?? '';
        $tagName = $this->attrs['tagName'] ?? 'div';

        // Auto-detect tag name if not set
        if ($tagName === 'div' || empty($tagName)) {
            if ($area === 'header' || str_contains($slug, 'header')) {
                $tagName = 'header';
            } elseif ($area === 'footer' || str_contains($slug, 'footer')) {
                $tagName = 'footer';
            }
        }

        $path = ($context['theme_path'] ?? '') . "/parts/{$slug}.php";
        if (!file_exists($path)) {
            $path = ($context['theme_path'] ?? '') . "/parts/{$slug}.html";
        }
        
        if (!file_exists($path)) return '';
        
        $content = file_get_contents($path);
        if (str_ends_with($path, '.php')) {
            ob_start();
            include $path;
            $content = ob_get_clean();
        }

        // We need a way to re-render. Since we are in an object, 
        // we might need the factory or renderer passed in context.
        if (isset($context['renderer_callback'])) {
            $renderedContent = ($context['renderer_callback'])($content);
        } else {
            // Fallback or error
            $renderedContent = $content; 
        }

        return "<{$tagName} class=\"wp-block-template-part\">{$renderedContent}</{$tagName}>";
    }
}
