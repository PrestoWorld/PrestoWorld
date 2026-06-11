<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\Gutenberg\Blocks;

/**
 * Core Block Entity - Used for both Rendering and Admin CRUD
 */
abstract class AbstractBlock
{
    public array $attrs = [];
    public array $innerBlocks = [];
    public string $innerHTML = '';
    public array $classes = [];
    public array $styles = [];

    public function __construct(array $data)
    {
        $this->attrs = $data['attrs'] ?? [];
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
