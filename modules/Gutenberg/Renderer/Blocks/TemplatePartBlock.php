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

        if (isset($context['template_part_registry'])) {
            $content = $context['template_part_registry']->get($slug);
        } else {
            // Fallback to manual loading if registry is missing
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
        }

        if (empty($content)) return '';

        // We need a way to re-render blocks within the part
        if (isset($context['renderer_callback'])) {
            $renderedContent = ($context['renderer_callback'])($content);
        } else {
            $renderedContent = $content; 
        }

        // WordPress standard: always include wp-block-template-part
        $classes = array_merge(['wp-block-template-part'], $this->classes);
        $classAttr = ' class="' . implode(' ', array_unique($classes)) . '"';
        
        return "<{$tagName}{$classAttr}>{$renderedContent}</{$tagName}>";
    }
}
