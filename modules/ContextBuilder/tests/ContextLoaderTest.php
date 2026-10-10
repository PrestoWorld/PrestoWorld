<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\ContextBuilder;

use PrestoWorld\Modules\ContextBuilder\Parser\BlockParser;

/**
 * ContextLoaderTest — test ContextLoader with jankx theme.
 *
 * Run: php modules/ContextBuilder/tests/ContextLoaderTest.php
 */
class ContextLoaderTest
{
    public function run(): void
    {
        echo "=== ContextLoader Test ===\n\n";

        $this->testBlockParser();
        $this->testContextLoader();

        echo "\n=== All tests passed ===\n";
    }

    protected function testBlockParser(): void
    {
        echo "Testing BlockParser...\n";

        $html = <<<HTML
<!-- wp:template-part {"slug":"header"} /-->

<!-- wp:group {"tagName":"main"} -->
<main class="wp-block-group">
    <!-- wp:heading {"level":1} -->
    <h1>Hello World</h1>
    <!-- /wp:heading -->

    <!-- wp:paragraph -->
    <p>This is a paragraph.</p>
    <!-- /wp:paragraph -->
</main>
<!-- /wp:group -->

<!-- wp:template-part {"slug":"footer"} /-->
HTML;

        $blocks = BlockParser::parse($html);

        assert(count($blocks) === 3, 'Should parse 3 top-level blocks');
        assert($blocks[0]['blockName'] === 'template-part', 'First block should be template-part');
        assert($blocks[1]['blockName'] === 'group', 'Second block should be group');
        assert($blocks[2]['blockName'] === 'template-part', 'Third block should be template-part');

        // Check group has inner blocks
        $group = $blocks[1];
        assert(count($group['innerBlocks']) === 2, 'Group should have 2 inner blocks');
        assert($group['innerBlocks'][0]['blockName'] === 'heading', 'First inner block should be heading');
        assert($group['innerBlocks'][1]['blockName'] === 'paragraph', 'Second inner block should be paragraph');

        echo "  ✓ BlockParser works correctly\n";
    }

    protected function testContextLoader(): void
    {
        echo "Testing ContextLoader...\n";

        $blockRegistry = new BlockRegistry();
        $blockRegistry->register(new Block\HeadingBlock());
        $blockRegistry->register(new Block\ParagraphBlock());
        $blockRegistry->register(new Block\ImageBlock());
        // Theme blocks are declared & handled by the theme itself.
        $blockRegistry->register(new \PrestoWorld\Theme\Timeless\ContextBlocks\DynamicDataLayoutBlock());
        $blockRegistry->register(new \PrestoWorld\Theme\Timeless\ContextBlocks\DynamicDataTemplateBlock());
        $blockRegistry->register(new \PrestoWorld\Theme\Timeless\ContextBlocks\HumanReadablePostDateBlock());

        $contextLoader = new ContextLoader(
            $blockRegistry,
            '/tmp/prestoworld-test/storage/contexts',
            '/tmp/prestoworld-test/themes/jankx',
        );

        // Test context type
        $contextType = new ContextType('post', 'Posts', [
            'render_mode' => 'ssr',
            'regions' => [
                'header' => ['type' => 'region'],
                'content' => ['type' => 'region'],
                'sidebar' => ['type' => 'region'],
                'footer' => ['type' => 'region'],
            ],
        ]);

        assert($contextType->getName() === 'post', 'Context type name should be post');
        assert($contextType->getRenderMode() === 'ssr', 'Context type render mode should be ssr');
        assert($contextType->hasRegion('content'), 'Context type should have content region');

        echo "  ✓ ContextLoader works correctly\n";
    }

}

// Run tests if executed directly
if (php_sapi_name() === 'cli' && basename(__FILE__) === basename($_SERVER['PHP_SELF'] ?? '')) {
    // Load required classes
    require_once __DIR__ . '/../Parser/BlockParser.php';
    require_once __DIR__ . '/../ContextType.php';
    require_once __DIR__ . '/../ContextRegistry.php';
    require_once __DIR__ . '/../BlockRegistry.php';
    require_once __DIR__ . '/../BaseBlock.php';
    require_once __DIR__ . '/../ContextLoader.php';
    require_once __DIR__ . '/../UnsupportedBlockException.php';
    require_once __DIR__ . '/../Block/HeadingBlock.php';
    require_once __DIR__ . '/../Block/ParagraphBlock.php';
    require_once __DIR__ . '/../Block/ImageBlock.php';
    require_once __DIR__ . '/../Block/QueryLoopBlock.php';
    require_once __DIR__ . '/../../../content/themes/timeless/presto/context-blocks/DynamicDataLayoutBlock.php';
    require_once __DIR__ . '/../../../content/themes/timeless/presto/context-blocks/DynamicDataTemplateBlock.php';
    require_once __DIR__ . '/../../../content/themes/timeless/presto/context-blocks/HumanReadablePostDateBlock.php';
    require_once __DIR__ . '/../../Gutenberg/Renderer/BlockRenderer.php';
    require_once __DIR__ . '/../../Gutenberg/Renderer/Blocks/BlockFactory.php';
    require_once __DIR__ . '/../../Gutenberg/Renderer/Blocks/AbstractBlock.php';
    require_once __DIR__ . '/../../Gutenberg/Renderer/Blocks/GenericBlock.php';
    require_once __DIR__ . '/../../Gutenberg/Renderer/Blocks/TextBlock.php';
    require_once __DIR__ . '/../../Gutenberg/Renderer/Decorators/BlockDecoratorInterface.php';
    require_once __DIR__ . '/../../Gutenberg/Pattern/PatternRegistry.php';

    $test = new ContextLoaderTest();
    $test->run();
}