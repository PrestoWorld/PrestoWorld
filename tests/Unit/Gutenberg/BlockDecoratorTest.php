<?php

declare(strict_types=1);

namespace Tests\Unit\Gutenberg;

use PHPUnit\Framework\TestCase;
use PrestoWorld\Modules\Gutenberg\Renderer\Decorators\LayoutDecorator;
use PrestoWorld\Modules\Gutenberg\Renderer\Decorators\StyleDecorator;

class BlockDecoratorTest extends TestCase
{
    /** @test */
    public function it_applies_layout_classes_with_wp_prefix()
    {
        $decorator = new LayoutDecorator();
        $block = [
            'blockName' => 'core/group',
            'attrs' => [
                'layout' => ['type' => 'constrained'],
                'align' => 'full'
            ],
            'classes' => []
        ];

        $decorator->decorate($block);

        $this->assertContains('wp-block-group', $block['classes']);
        $this->assertContains('is-layout-constrained', $block['classes']);
        $this->assertContains('wp-block-group-is-layout-constrained', $block['classes']);
        $this->assertContains('alignfull', $block['classes']);
        $this->assertContains('has-global-padding', $block['classes']);
    }

    /** @test */
    public function it_applies_style_decorators_for_colors_and_spacing()
    {
        $decorator = new StyleDecorator();
        $block = [
            'blockName' => 'core/paragraph',
            'attrs' => [
                'backgroundColor' => 'vivid-red',
                'textColor' => 'white',
                'style' => [
                    'spacing' => [
                        'padding' => [
                            'top' => 'var:preset|spacing|30'
                        ]
                    ]
                ]
            ],
            'classes' => [],
            'styles' => []
        ];

        $decorator->decorate($block);

        $this->assertContains('has-background', $block['classes']);
        $this->assertContains('has-vivid-red-background-color', $block['classes']);
        $this->assertContains('has-text-color', $block['classes']);
        $this->assertContains('has-white-color', $block['classes']);
        
        $this->assertContains('padding-top:var(--wp--preset--spacing--30)', $block['styles']);
    }

    /** @test */
    public function it_handles_navigation_specific_justification()
    {
        $decorator = new LayoutDecorator();
        $block = [
            'blockName' => 'core/navigation',
            'attrs' => [
                'layout' => [
                    'type' => 'flex',
                    'justifyContent' => 'space-between'
                ]
            ],
            'classes' => []
        ];

        $decorator->decorate($block);

        $this->assertContains('is-content-justification-space-between', $block['classes']);
    }
}
