<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\Gutenberg\Renderer\Blocks;

/**
 * Pattern Block rendering core/pattern
 */
class PatternBlock extends AbstractBlock
{
    public function render(array $context): string
    {
        $slug = $this->attrs['slug'] ?? '';
        if (empty($slug)) return '';

        // We need the pattern registry. It might be in the context or registry.
        // Assuming the renderer_callback can handle the re-rendering of the pattern content.
        
        // This block needs access to the PatternRegistry. 
        // We can pass it via context or use a global. Since this is an MVP, 
        // let's assume it can be resolved or provided.
        
        if (isset($context['pattern_registry'])) {
            $content = $context['pattern_registry']->get($slug);
        } else {
            // Fallback: If not in context, we might have an issue.
            // But let's try to get it from the renderer if possible.
            $content = ''; 
        }

        if (empty($content)) return '';

        if (isset($context['renderer_callback'])) {
            return ($context['renderer_callback'])($content);
        }

        return $content;
    }
}
