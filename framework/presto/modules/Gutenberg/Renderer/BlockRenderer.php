<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\Gutenberg\Renderer;

use PrestoWorld\Modules\Gutenberg\Pattern\PatternRegistry;
use PrestoWorld\Modules\Gutenberg\Renderer\Decorators\BlockDecoratorInterface;
use PrestoWorld\Modules\Gutenberg\Renderer\Blocks\BlockFactory;

/**
 * Hyper-Optimized Block Renderer with Context Support
 */
class BlockRenderer
{
    protected array $registry = [];
    protected array $decorators = [];
    protected ?PatternRegistry $patternRegistry = null;
    protected array $context = [];

    public function setPatternRegistry(PatternRegistry $registry): void
    {
        $this->patternRegistry = $registry;
    }

    public function setContext(array $context): void
    {
        $this->context = $context;
    }

    public function addDecorator(BlockDecoratorInterface $decorator): void
    {
        $this->decorators[] = $decorator;
    }

    public function register(string $name, callable $callback): void
    {
        $this->registry[$name] = $callback;
    }

    public function render(array $blocks): string
    {
        $output = '';
        foreach ($blocks as $block) {
            $output .= $this->renderBlock($block);
        }
        return $output;
    }

    public function renderBlock(array &$block, array $localContext = []): string
    {
        try {
            $name = $block['blockName'];

            // Check for custom callback registry first
            if ($name && isset($this->registry[$name])) {
                $inner = '';
                foreach ($block['innerBlocks'] as $innerData) {
                    $inner .= $this->renderBlock($innerData, $localContext);
                }
                return ($this->registry[$name])($block['attrs'] ?? [], $inner);
            }

            if ($name === null) {
                return $block['innerHTML'] ?? '';
            }

            // Recursively apply Decorators
            $this->decorateRecursive($block);

            // Create the block instance using the Factory
            $instance = BlockFactory::create($block);
            
            // Merge global context with local context
            $context = array_merge($this->context, $localContext, [
                'pattern_registry' => $this->patternRegistry,
                'renderer_callback' => function(string $html) {
                    if (!isset($this->registry['__rerender'])) return $html;
                    return ($this->registry['__rerender'])($html);
                }
            ]);

            return $instance->render($context);
        } catch (\Throwable $e) {
            // Log or display error in dev mode
            if (isset($_SERVER['WP_DEBUG']) && $_SERVER['WP_DEBUG']) {
                return "<!-- Block Error ($name): " . htmlspecialchars($e->getMessage()) . " -->";
            }
            return $block['innerHTML'] ?? '';
        }
    }

    protected function decorateRecursive(array &$block): void
    {
        // Populate defaults
        $block['classes'] = $block['classes'] ?? [];
        $block['styles'] = $block['styles'] ?? [];

        // Decorate current block
        foreach ($this->decorators as $decorator) {
            $decorator->decorate($block);
        }

        // Recursively decorate inner blocks
        if (!empty($block['innerBlocks'])) {
            foreach ($block['innerBlocks'] as &$innerBlock) {
                $this->decorateRecursive($innerBlock);
            }
        }
    }
}
