<?php

declare(strict_types=1);

namespace Tests\Unit\Gutenberg;

use PHPUnit\Framework\TestCase;
use PrestoWorld\Modules\Gutenberg\Parser\BlockParser;

class BlockParserTest extends TestCase
{
    private BlockParser $parser;

    protected function setUp(): void
    {
        $this->parser = new BlockParser();
    }

    public function test_it_parses_simple_block(): void
    {
        $content = '<!-- wp:paragraph {"fontSize":"large"} -->
<p>Hello World</p>
<!-- /wp:paragraph -->';

        $blocks = $this->parser->parse($content);

        $this->assertCount(1, $blocks);
        $this->assertEquals('core/paragraph', $blocks[0]['blockName']);
        $this->assertEquals(['fontSize' => 'large'], $blocks[0]['attrs']);
        $this->assertStringContainsString('<p>Hello World</p>', $blocks[0]['innerHTML']);
    }

    public function test_it_parses_void_block(): void
    {
        $content = '<!-- wp:separator /-->';

        $blocks = $this->parser->parse($content);

        $this->assertCount(1, $blocks);
        $this->assertEquals('core/separator', $blocks[0]['blockName']);
        $this->assertEmpty($blocks[0]['innerBlocks']);
    }

    public function test_it_parses_nested_blocks(): void
    {
        $content = '<!-- wp:group -->
<div class="wp-block-group">
<!-- wp:paragraph -->
<p>Nested Paragraph</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->';

        $blocks = $this->parser->parse($content);

        $this->assertCount(1, $blocks);
        $this->assertEquals('core/group', $blocks[0]['blockName']);
        // Group block contains 1 inner block: the paragraph
        // Outer wrapper HTML (<div> / </div>) and whitespace are accumulated in innerHTML, not as separate innerBlocks
        $this->assertCount(1, $blocks[0]['innerBlocks']);
        $this->assertEquals('core/paragraph', $blocks[0]['innerBlocks'][0]['blockName']);
    }

    public function test_it_handles_mixed_content(): void
    {
        $content = 'Leading text
<!-- wp:spacer {"height":20} /-->
Trailing text';

        $blocks = $this->parser->parse($content);

        $this->assertCount(3, $blocks);
        $this->assertNull($blocks[0]['blockName']);
        $this->assertEquals("Leading text\n", $blocks[0]['innerHTML']);
        
        $this->assertEquals('core/spacer', $blocks[1]['blockName']);
        
        $this->assertNull($blocks[2]['blockName']);
        $this->assertEquals("\nTrailing text", $blocks[2]['innerHTML']);
    }

    public function test_it_parses_many_blocks_efficiently(): void
    {
        $content = '';
        for ($i = 0; $i < 100; $i++) {
            $content .= "<!-- wp:test-block-{$i} /-->\n";
        }

        $startMemory = memory_get_usage();
        $blocks = $this->parser->parse($content);
        $endMemory = memory_get_usage();

        $this->assertCount(100, $blocks);
        // Ensure memory usage didn't spike significantly (this is a loose check)
        $this->assertLessThan(1024 * 1024, $endMemory - $startMemory, "Memory usage spiked too high");
    }
}
