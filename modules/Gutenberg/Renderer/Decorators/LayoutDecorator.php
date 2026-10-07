<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\Gutenberg\Renderer\Decorators;

/**
 * Decorates blocks with WordPress layout-specific classes
 */
class LayoutDecorator implements BlockDecoratorInterface
{
    public function decorate(array &$block): void
    {
        $attrs = &$block['attrs'];
        $name = $block['blockName'] ?? '';
        
        // Ensure classes array exists
        $block['classes'] = $block['classes'] ?? [];

        if ($name) {
            // Add base block class (e.g. wp-block-group)
            $cleanName = str_replace('/', '-', $name);
            $wpName = str_replace('core-', 'wp-block-', $cleanName);
            $block['classes'][] = $wpName;

            // Handle alignment
            if (!empty($attrs['align'])) {
                $block['classes'][] = 'align' . $attrs['align'];
            }
        }

        // Handle layout classes
        if (!empty($attrs['layout'])) {
            $layout = $attrs['layout'];
            $type = $layout['type'] ?? 'flow';
            if ($type === 'default') $type = 'flow';

            $block['classes'][] = "is-layout-{$type}";
            
            if ($name) {
                $wpName = str_replace('core-', 'wp-block-', str_replace('/', '-', $name));
                $block['classes'][] = "{$wpName}-is-layout-{$type}";
            }

            if ($type === 'constrained') {
                $block['classes'][] = 'has-global-padding';
            }

            // Flex/Grid specific justifications
            if (!empty($layout['justifyContent'])) {
                $justification = str_replace(' ', '-', $layout['justifyContent']);
                $block['classes'][] = 'is-content-justification-' . $justification;
                
                // Navigation specific justification
                if ($name === 'core/navigation') {
                    $block['classes'][] = 'items-justified-' . $justification;
                }
            }

            if (!empty($layout['flexWrap']) && $layout['flexWrap'] === 'nowrap') {
                $block['classes'][] = 'is-nowrap';
            }

            if (!empty($layout['orientation']) && $layout['orientation'] === 'vertical') {
                $block['classes'][] = 'is-vertical';
            }
        }

        // Navigation responsive class
        if ($name === 'core/navigation') {
            $block['classes'][] = 'is-responsive';
        }
    }
}
