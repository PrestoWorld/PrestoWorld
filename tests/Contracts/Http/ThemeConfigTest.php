<?php

declare(strict_types=1);

namespace Tests\Contracts\Http;

use PHPUnit\Framework\TestCase;
use App\Contracts\Http\ThemeConfig;

class ThemeConfigTest extends TestCase
{
    public function testFromArrayCreatesConfig(): void
    {
        $config = ThemeConfig::fromArray([
            'default_title' => 'Test Title',
            'charset' => 'UTF-8',
            'viewport' => 'width=device-width',
            'css_reset' => 'body { margin: 0; }',
        ]);

        $this->assertInstanceOf(ThemeConfig::class, $config);
    }

    public function testFromArrayUsesDefaults(): void
    {
        $config = ThemeConfig::fromArray([]);

        $this->assertSame('PrestoWorld', $config->defaultTitle);
        $this->assertSame('UTF-8', $config->charset);
        $this->assertSame('width=device-width, initial-scale=1.0', $config->viewport);
        $this->assertStringContainsString('box-sizing: border-box', $config->cssReset);
    }

    public function testFromArrayUsesProvidedValues(): void
    {
        $config = ThemeConfig::fromArray([
            'default_title' => 'Custom Title',
            'charset' => 'ISO-8859-1',
            'viewport' => 'width=device-width, initial-scale=2.0',
            'css_reset' => 'body { margin: 10px; }',
        ]);

        $this->assertSame('Custom Title', $config->defaultTitle);
        $this->assertSame('ISO-8859-1', $config->charset);
        $this->assertSame('width=device-width, initial-scale=2.0', $config->viewport);
        $this->assertSame('body { margin: 10px; }', $config->cssReset);
    }

    public function testConstructorSetsProperties(): void
    {
        $config = new ThemeConfig(
            defaultTitle: 'Test',
            charset: 'UTF-8',
            viewport: 'width=device-width',
            cssReset: 'body { margin: 0; }',
        );

        $this->assertSame('Test', $config->defaultTitle);
        $this->assertSame('UTF-8', $config->charset);
        $this->assertSame('width=device-width', $config->viewport);
        $this->assertSame('body { margin: 0; }', $config->cssReset);
    }
}
