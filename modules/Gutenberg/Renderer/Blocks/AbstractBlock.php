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

    protected function renderInner(array $context): string
    {
        $output = '';
        foreach ($this->innerBlocks as $block) {
            $output .= $block->render($context);
        }
        return $output;
    }
}
