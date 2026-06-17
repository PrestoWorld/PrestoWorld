<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\Gutenberg\Renderer\Blocks;

/**
 * Factory to create specific Block Instances
 */
class BlockFactory
{
    protected static array $map = [
        'core/group'         => GroupBlock::class,
        'core/query'         => QueryBlock::class,
        'core/post-template' => PostTemplateBlock::class,
        'core/post-title'    => PostTitleBlock::class,
        'core/site-title'          => SiteTitleBlock::class,
        'core/site-logo'           => SiteLogoBlock::class,
        'core/site-tagline'        => SiteTaglineBlock::class,
        'core/template-part'       => TemplatePartBlock::class,
        'core/navigation'          => NavigationBlock::class,
        'core/navigation-link'     => NavigationLinkBlock::class,
        'core/paragraph'           => ParagraphBlock::class,
        'core/heading'             => HeadingBlock::class,
        'core/post-date'           => PostDateBlock::class,
        'core/post-content'        => PostContentBlock::class,
        'core/post-featured-image' => PostFeaturedImageBlock::class,
        'core/spacer'              => SpacerBlock::class,
        'core/columns'             => ColumnsBlock::class,
        'core/column'              => ColumnBlock::class,
        'core/buttons'             => ButtonsBlock::class,
        'core/button'              => ButtonBlock::class,
        'core/pattern'             => PatternBlock::class,
    ];

    public static function create(array $data): AbstractBlock
    {
        $name = $data['blockName'] ?? null;
        
        if ($name === null) {
            return new TextBlock($data);
        }

        $class = self::$map[$name] ?? GenericBlock::class;
        $instance = new $class($data);

        // Convert children recursively
        if (!empty($data['innerBlocks'])) {
            $innerInstances = [];
            foreach ($data['innerBlocks'] as $innerData) {
                $innerInstances[] = self::create($innerData);
            }
            $instance->setInnerBlocks($innerInstances);
        }

        return $instance;
    }
}
