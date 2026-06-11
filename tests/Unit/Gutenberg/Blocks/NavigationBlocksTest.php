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
        
        $this->assertStringContainsString('class="custom-nav-item wp-block-navigation-item wp-block-navigation-link"', $html);
        $this->assertStringContainsString('href="/about"', $html);
        $this->assertStringContainsString('About Us', $html);
    }

    public function test_navigation_block_renders_with_responsive_container(): void
    {
        $nav = new NavigationBlock(['classes' => ['main-menu']]);
        $link = new NavigationLinkBlock(['attrs' => ['label' => 'Home', 'url' => '/']]);
        $nav->setInnerBlocks([$link]);
        
        $html = $nav->render([]);
        
        // Check Interactivity API
        $this->assertStringContainsString('data-wp-interactive="core/navigation"', $html);
        // Check Responsive Button
        $this->assertStringContainsString('class="wp-block-navigation__responsive-container-open"', $html);
        // Check UL Container and Inner Content
        $this->assertStringContainsString('<ul class="wp-block-navigation__container main-menu">', $html);
        $this->assertStringContainsString('Home', $html);
    }
}
