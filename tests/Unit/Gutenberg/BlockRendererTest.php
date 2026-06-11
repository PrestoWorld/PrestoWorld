<?php

declare(strict_types=1);

namespace Tests\Unit\Gutenberg;

use PHPUnit\Framework\TestCase;
use PrestoWorld\Modules\Gutenberg\Renderer\BlockRenderer;
use PrestoWorld\Modules\Gutenberg\Pattern\PatternRegistry;
use PrestoWorld\Modules\Gutenberg\Pattern\MemoryStorage;

use PrestoWorld\Modules\Gutenberg\Renderer\Decorators\LayoutDecorator;
use PrestoWorld\Modules\Gutenberg\Renderer\Decorators\StyleDecorator;

class BlockRendererTest extends TestCase
{
    private BlockRenderer $renderer;

    protected function setUp(): void
    {
        $this->renderer = new BlockRenderer();
        $this->renderer->addDecorator(new LayoutDecorator());
        $this->renderer->addDecorator(new StyleDecorator());
        
        $this->renderer->setContext([
            'site_title' => 'Test Site',
            'site_url' => 'http://test.com',
            'theme_path' => '/tmp'
        ]);
        
        // Mock PatternRegistry
        $patterns = new PatternRegistry('/tmp');
        $patterns->setStorage(new MemoryStorage());
        $this->renderer->setPatternRegistry($patterns);
    }

    public function test_it_renders_simple_static_block_from_inner_html(): void
    {
        $block = [
            'blockName' => 'core/paragraph',
            'attrs' => [],
            'innerBlocks' => [],
            'innerHTML' => '<p>Static Content</p>'
        ];

        $html = $this->renderer->renderBlock($block);
        
        // Should use innerHTML directly (no double tagging)
        $this->assertEquals('<p>Static Content</p>', $html);
    }

    public function test_it_renders_dynamic_group_block(): void
    {
        $block = [
            'blockName' => 'core/group',
            'attrs' => ['tagName' => 'main', 'align' => 'full'],
            'innerBlocks' => [
                [
                    'blockName' => null,
                    'attrs' => [],
                    'innerBlocks' => [],
                    'innerHTML' => 'Hello'
                ]
            ],
            'innerHTML' => ''
        ];

        $html = $this->renderer->renderBlock($block);
        
        $this->assertEquals('<main class="wp-block-group alignfull">Hello</main>', $html);
    }

    public function test_it_renders_site_title(): void
    {
        $block = [
            'blockName' => 'core/site-title',
            'attrs' => ['level' => 1],
            'innerBlocks' => [],
            'innerHTML' => ''
        ];

        $html = $this->renderer->renderBlock($block);
        
        $this->assertStringContainsString('<h1 class="wp-block-site-title">', $html);
        $this->assertStringContainsString('Test Site', $html);
    }

    public function test_it_resolves_wp_variables_in_styles(): void
    {
        $block = [
            'blockName' => 'core/group',
            'attrs' => [
                'style' => [
                    'spacing' => [
                        'padding' => ['top' => 'var:preset|spacing|50']
                    ]
                ]
            ],
            'innerBlocks' => [],
            'innerHTML' => 'Content'
        ];

        $html = $this->renderer->renderBlock($block);
        
        $this->assertStringContainsString('style="padding-top:var(--wp--preset--spacing--50)"', $html);
    }

    public function test_it_handles_custom_registry_callbacks(): void
    {
        $this->renderer->register('custom/test', function($attrs, $inner) {
            return "CUSTOM: " . ($attrs['val'] ?? '') . " - " . $inner;
        });

        $block = [
            'blockName' => 'custom/test',
            'attrs' => ['val' => '123'],
            'innerBlocks' => [
                [
                    'blockName' => null,
                    'attrs' => [],
                    'innerBlocks' => [],
                    'innerHTML' => 'Inner'
                ]
            ],
            'innerHTML' => ''
        ];

        $html = $this->renderer->renderBlock($block);
        
        $this->assertEquals('CUSTOM: 123 - Inner', $html);
    }

    public function test_it_decorates_inner_blocks_recursively(): void
    {
        $block = [
            'blockName' => 'core/group',
            'attrs' => ['layout' => ['type' => 'constrained']],
            'innerBlocks' => [
                [
                    'blockName' => 'core/group',
                    'attrs' => ['align' => 'wide'],
                    'innerBlocks' => [],
                    'innerHTML' => 'Nested'
                ]
            ],
            'innerHTML' => ''
        ];

        $html = $this->renderer->renderBlock($block);
        
        // Parent should have constrained classes
        $this->assertStringContainsString('is-layout-constrained', $html);
        // Child should have wide class
        $this->assertStringContainsString('alignwide', $html);
        $this->assertStringContainsString('wp-block-group', $html);
    }
}
