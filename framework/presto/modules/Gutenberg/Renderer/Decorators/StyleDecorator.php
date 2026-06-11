<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\Gutenberg\Renderer\Decorators;

/**
 * Decorates blocks with CSS styles and related classes
 */
class StyleDecorator implements BlockDecoratorInterface
{
    public function decorate(array &$block): void
    {
        $attrs = &$block['attrs'];
        $block['classes'] = $block['classes'] ?? [];
        $block['styles'] = $block['styles'] ?? [];

        // 1. Handle Colors
        if (!empty($attrs['backgroundColor'])) {
            $block['classes'][] = 'has-background';
            $block['classes'][] = 'has-' . $attrs['backgroundColor'] . '-background-color';
        }
        if (!empty($attrs['textColor'])) {
            $block['classes'][] = 'has-text-color';
            $block['classes'][] = 'has-' . $attrs['textColor'] . '-color';
        }

        // 2. Handle Typography
        if (!empty($attrs['fontSize'])) {
            $block['classes'][] = 'has-' . $attrs['fontSize'] . '-font-size';
        }

        // 3. Handle Inline Spacing Styles
        if (!empty($attrs['style']['spacing'])) {
            $spacing = $attrs['style']['spacing'];
            foreach (['padding', 'margin'] as $type) {
                if (isset($spacing[$type])) {
                    foreach (['top', 'bottom', 'left', 'right'] as $side) {
                        if (isset($spacing[$type][$side])) {
                            $val = $this->resolveVar($spacing[$type][$side]);
                            $block['styles'][] = "{$type}-{$side}:{$val}";
                        }
                    }
                }
            }
        }
    }

    protected function resolveVar(string $value): string
    {
        if (str_starts_with($value, 'var:')) {
            return 'var(--wp--' . str_replace(['var:', '|'], ['', '--'], $value) . ')';
        }
        return $value;
    }
}
