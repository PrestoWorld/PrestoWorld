<?php

declare(strict_types=1);

namespace Tests\Unit\Gutenberg\Blocks;

use PHPUnit\Framework\TestCase;
use PrestoWorld\Modules\Gutenberg\Renderer\Blocks\GroupBlock;
use PrestoWorld\Modules\Gutenberg\Renderer\Blocks\SiteTitleBlock;
use PrestoWorld\Modules\Gutenberg\Renderer\Blocks\SpacerBlock;
use PrestoWorld\Modules\Gutenberg\Renderer\Blocks\SiteLogoBlock;

class CoreBlocksTest extends TestCase
{
    public function test_group_block_renders_with_tag_name(): void
    {
        // Note: wp-block-group class is added by LayoutDecorator at render time.
        // The block itself only uses classes passed in constructor.
        $block = new GroupBlock([
            'attrs' => ['tagName' => 'section'],
            'classes' => ['is-layout-constrained']
        ]);
        
        $html = $block->render([]);
        
        $this->assertStringStartsWith('<section', $html);
        $this->assertStringContainsString('is-layout-constrained', $html);
        $this->assertStringEndsWith('</section>', $html);
    }

    public function test_site_title_block_level_handling(): void
    {
        $block0 = new SiteTitleBlock(['attrs' => ['level' => 0], 'classes' => ['site-title']]);
        $html0 = $block0->render(['site_title' => 'My Site', 'site_url' => '/']);
        $this->assertStringContainsString('<p class="site-title">', $html0);

        $block1 = new SiteTitleBlock(['attrs' => ['level' => 1], 'classes' => ['site-title']]);
        $html1 = $block1->render(['site_title' => 'My Site', 'site_url' => '/']);
        $this->assertStringContainsString('<h1 class="site-title">', $html1);
    }

    public function test_spacer_block_styles(): void
    {
        $block = new SpacerBlock([
            'attrs' => [],
            'classes' => ['wp-block-spacer'],
            'styles' => ['height:100px']
        ]);
        
        $html = $block->render([]);
        $this->assertStringContainsString('style="height:100px"', $html);
        $this->assertStringContainsString('aria-hidden="true"', $html);
    }

    public function test_site_logo_render(): void
    {
        $block = new SiteLogoBlock(['classes' => ['my-logo']]);
        $html = $block->render(['site_logo_url' => 'logo.png']);
        
        $this->assertStringContainsString('class="my-logo"', $html);
        $this->assertStringContainsString('src="logo.png"', $html);
    }
}
