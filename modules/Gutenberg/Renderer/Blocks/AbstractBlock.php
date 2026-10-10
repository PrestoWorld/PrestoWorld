<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\Gutenberg\Renderer\Blocks;

/**
 * Base Abstract Block for High-Performance Rendering
 */
abstract class AbstractBlock
{
    public array $attrs = [];
    public array $innerBlocks = [];
    public string $innerHTML = '';
    public array $classes = [];
    public array $styles = [];

    public string $name = '';

    public function __construct(array $data)
    {
        $this->name    = $data['blockName'] ?? '';
        $this->attrs   = $data['attrs'] ?? [];
        $this->classes = $data['classes'] ?? [];
        $this->styles  = $data['styles'] ?? [];
        $this->innerHTML = $data['innerHTML'] ?? '';
    }

    public function setInnerBlocks(array $blocks): void
    {
        $this->innerBlocks = $blocks;
    }

    abstract public function render(array $context): string;

    /**
     * PHP port of get_block_wrapper_attributes().
     *
     * Combines the block's computed classes/styles (populated by the renderer
     * decorators) with any extra attributes (e.g. `class`, `aria-label`).
     *
     * Returns the attributes string WITH a leading space so callers can do
     * `<tag{$this->wrapperAttributes()}>`. Block base classes are provided by
     * the LayoutDecorator (`wp-block-*`), mirroring the fork output.
     *
     * @param array<string, string> $extra
     */
    protected function wrapperAttributes(array $extra = []): string
    {
        $classes = $this->classes;
        $styles  = $this->styles;

        // Extra classes and style are merged like get_block_wrapper_attributes().
        if (!empty($extra['class'])) {
            foreach (preg_split('/\s+/', trim((string) $extra['class'])) as $class) {
                if ($class !== '') {
                    $classes[] = $class;
                }
            }
        }
        if (!empty($extra['style'])) {
            foreach (explode(';', (string) $extra['style']) as $style) {
                $style = trim($style);
                if ($style !== '') {
                    $styles[] = $style;
                }
            }
        }

        $classes = array_values(array_unique(array_filter($classes, static fn ($c) => $c !== '')));
        $styles  = array_values(array_filter(array_map(static fn ($s) => rtrim(trim((string) $s), ';'), $styles), static fn ($s) => $s !== ''));

        $attributes = [];

        if ($classes !== []) {
            $attributes['class'] = implode(' ', $classes);
        }
        if ($styles !== []) {
            $attributes['style'] = implode(';', $styles) . ';';
        }

        foreach ($extra as $key => $value) {
            if ($key === 'class' || $key === 'style') {
                continue;
            }
            if ($value === '' || $value === null) {
                continue;
            }
            $attributes[$key] = (string) $value;
        }

        $out = '';
        foreach ($attributes as $key => $value) {
            $out .= ' ' . $key . '="' . htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '"';
        }

        return $out;
    }

    protected function renderInner(array $context): string
    {
        $output = '';
        foreach ($this->innerBlocks as $block) {
            $output .= $block->render($context);
        }
        return $output;
    }
}
