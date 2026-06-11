<?php

declare(strict_types=1);

namespace Tests\Unit\Gutenberg\Blocks;

use PHPUnit\Framework\TestCase;
use PrestoWorld\Modules\Gutenberg\Renderer\Blocks\NavigationBlock;
use PrestoWorld\Modules\Gutenberg\Renderer\Blocks\NavigationLinkBlock;

class NavigationBlocksTest extends TestCase
{
    public function test_navigation_link_renders_correctly(): void
    {
        $block = new NavigationLinkBlock([
            'attrs' => ['label' => 'About Us', 'url' => '/about'],
            'classes' => ['custom-nav-item']
        ]);
        
        $html = $block->render([]);
        
        // WordPress standard: wp-block-navigation-item is always included
        $this->assertStringContainsString('wp-block-navigation-item', $html);
        $this->assertStringContainsString('custom-nav-item', $html);
        $this->assertStringContainsString('href="/about"', $html);
        $this->assertStringContainsString('About Us', $html);
        // Label is wrapped in span
        $this->assertStringContainsString('wp-block-navigation-item__label', $html);
    }

    public function test_navigation_block_renders_with_responsive_container(): void
    {
        $nav = new NavigationBlock(['classes' => ['main-menu'], 'attrs' => ['overlayMenu' => 'always']]);
        $link = new NavigationLinkBlock(['attrs' => ['label' => 'Home', 'url' => '/']]);
        $nav->setInnerBlocks([$link]);
        
        $html = $nav->render([]);
        
        // Check Interactivity API
        $this->assertStringContainsString('data-wp-interactive="core/navigation"', $html);
        // Check Responsive Button
        $this->assertStringContainsString('class="wp-block-navigation__responsive-container-open"', $html);
        // Check UL Container and Inner Content
        $this->assertStringContainsString('wp-block-navigation__container', $html);
        $this->assertStringContainsString('Home', $html);
        // Check is-responsive class
        $this->assertStringContainsString('is-responsive', $html);
    }
}
