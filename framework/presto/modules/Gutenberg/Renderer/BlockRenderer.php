<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\Gutenberg\Renderer;

/**
 * Optimized Block Renderer
 * 
 * Handles tree traversal and on-demand block rendering.
 * Does not pre-initialize blocks; resolves them only when needed.
 */
class BlockRenderer
{
    protected array $registry = [];

    /**
     * Register a block render callback
     */
    public function register(string $name, callable $callback): void
    {
        $this->registry[$name] = $callback;
    }

    /**
     * Render a list of blocks
     */
    public function render(array $blocks): string
    {
        $output = '';
        foreach ($blocks as $block) {
            $output .= $this->renderBlock($block);
        }
        return $output;
    }

    /**
     * Render a single block
     */
    public function renderBlock(array $block): string
    {
        $name = $block['blockName'];

        // Text content (non-block)
        if ($name === null) {
            return $block['innerHTML'] ?? '';
        }

        // Check if block has children and render them first (Post-order traversal for speed if needed, 
        // but here standard pre-order style is usually expected for HTML structure)
        $innerHtml = '';
        if (!empty($block['innerBlocks'])) {
            $innerHtml = $this->render($block['innerBlocks']);
        }

        // If block is registered, use its callback
        if (isset($this->registry[$name])) {
            return ($this->registry[$name])($block['attrs'], $innerHtml, $block);
        }

        // Fallback: Just return innerHTML if it exists (for blocks with saved HTML)
        if (isset($block['innerHTML']) && trim($block['innerHTML']) !== '') {
            return $block['innerHTML'];
        }

        return $innerHtml;
    }
}
