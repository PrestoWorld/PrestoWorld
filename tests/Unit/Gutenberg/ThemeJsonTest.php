<?php

declare(strict_types=1);

namespace Tests\Unit\Gutenberg;

use PHPUnit\Framework\TestCase;
use PrestoWorld\Modules\Gutenberg\Theme\ThemeJson;

class ThemeJsonTest extends TestCase
{
    private string $tempThemePath;

    protected function setUp(): void
    {
        $this->tempThemePath = sys_get_temp_dir() . '/presto_test_theme';
        if (!is_dir($this->tempThemePath)) {
            mkdir($this->tempThemePath, 0777, true);
        }

        $themeJson = [
            'settings' => [
                'color' => [
                    'palette' => [
                        ['slug' => 'base', 'color' => '#ffffff'],
                        ['slug' => 'contrast', 'color' => '#000000']
                    ]
                ],
                'typography' => [
                    'fontSizes' => [
                        ['slug' => 'medium', 'size' => '1rem']
                    ]
                ]
            ],
            'styles' => [
                'color' => [
                    'background' => 'var:preset|color|base',
                    'text' => 'var:preset|color|contrast'
                ]
            ]
        ];

        file_put_contents($this->tempThemePath . '/theme.json', json_encode($themeJson));
    }

    protected function tearDown(): void
    {
        if (file_exists($this->tempThemePath . '/theme.json')) {
            unlink($this->tempThemePath . '/theme.json');
        }
        if (is_dir($this->tempThemePath)) {
            rmdir($this->tempThemePath);
        }
    }

    public function test_it_compiles_css_presets(): void
    {
        $themeJson = new ThemeJson($this->tempThemePath);
        $css = $themeJson->compile();

        $this->assertStringContainsString('--wp--preset--color--base: #ffffff', $css);
        $this->assertStringContainsString('--wp--preset--color--contrast: #000000', $css);
        $this->assertStringContainsString('--wp--preset--font-size--medium: 1rem', $css);
    }

    public function test_it_compiles_global_styles(): void
    {
        $themeJson = new ThemeJson($this->tempThemePath);
        $css = $themeJson->compile();

        $this->assertStringContainsString('background-color: var(--wp--preset--color--base)', $css);
        $this->assertStringContainsString('color: var(--wp--preset--color--contrast)', $css);
    }

    public function test_it_gets_settings_using_dot_notation(): void
    {
        $themeJson = new ThemeJson($this->tempThemePath);
        
        $palette = $themeJson->getSetting('color.palette');
        $this->assertCount(2, $palette);
        $this->assertEquals('base', $palette[0]['slug']);

        $fontSize = $themeJson->getSetting('typography.fontSizes.0.size');
        $this->assertEquals('1rem', $fontSize);
    }
}
