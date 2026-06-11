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
        $block = new GroupBlock([
            'attrs' => ['tagName' => 'section'],
            'classes' => ['is-layout-constrained']
        ]);
        
        $html = $block->render([]);
        
        // WordPress standard: wp-block-group is always included
        $this->assertStringContainsString('wp-block-group', $html);
        $this->assertStringContainsString('is-layout-constrained', $html);
        $this->assertStringStartsWith('<section', $html);
        $this->assertStringEndsWith('</section>', $html);
    }

    public function test_site_title_block_level_handling(): void
    {
        $block0 = new SiteTitleBlock(['attrs' => ['level' => 0], 'classes' => ['site-title']]);
        $html0 = $block0->render(['site_title' => 'My Site', 'site_url' => '/']);
        // WordPress standard: wp-block-site-title is always included
        $this->assertStringContainsString('wp-block-site-title', $html0);
        $this->assertStringContainsString('site-title', $html0);
        $this->assertStringContainsString('target="_self"', $html0);
        $this->assertStringContainsString('<p', $html0);

        $block1 = new SiteTitleBlock(['attrs' => ['level' => 1], 'classes' => ['site-title']]);
        $html1 = $block1->render(['site_title' => 'My Site', 'site_url' => '/']);
        $this->assertStringContainsString('wp-block-site-title', $html1);
        $this->assertStringContainsString('site-title', $html1);
        $this->assertStringContainsString('<h1', $html1);
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
        
        // WordPress standard: wp-block-site-logo is always included
        $this->assertStringContainsString('wp-block-site-logo', $html);
        $this->assertStringContainsString('my-logo', $html);
        $this->assertStringContainsString('src="logo.png"', $html);
        // is-default-size class when no width is specified
        $this->assertStringContainsString('is-default-size', $html);
    }
}
