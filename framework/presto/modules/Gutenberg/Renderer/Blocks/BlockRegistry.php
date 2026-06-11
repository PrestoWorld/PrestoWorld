<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\Gutenberg\Renderer\Blocks;

/**
 * High-Performance O(1) Block Registry
 */
class BlockRegistry
{
    /**
     * Hash map for O(1) lookup performance
     */
    protected array $map = [];

    public function __construct()
    {
        // Core blocks pre-mapped
        $this->map = [
            'core/group'         => GroupBlock::class,
            'core/query'         => QueryBlock::class,
            'core/post-template' => PostTemplateBlock::class,
            'core/site-title'    => SiteTitleBlock::class,
            'core/template-part' => TemplatePartBlock::class,
            'core/navigation'          => NavigationBlock::class,
            'core/post-date'           => PostDateBlock::class,
            'core/post-content'        => PostContentBlock::class,
            'core/post-featured-image' => PostFeaturedImageBlock::class,
            'core/spacer'              => SpacerBlock::class,
        ];
    }

    /**
     * Register a block. Hash map ensures O(1) even with thousands of blocks.
     */
    public function register(string $blockName, string $blockClass): void
    {
        $this->map[$blockName] = $blockClass;
    }

    public function get(string $blockName): ?string
    {
        return $this->map[$blockName] ?? null;
    }

    public function has(string $blockName): bool
    {
        return isset($this->map[$blockName]);
    }
}
