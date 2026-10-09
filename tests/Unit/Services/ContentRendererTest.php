<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use App\Services\ContentRenderer;
use App\Services\NullContentRenderer;
use App\Contracts\Services\RenderedContent;
use PrestoWorld\Modules\ClassicTheme\ClassicThemeEngine;
use PrestoWorld\Modules\ContextBuilder\ContextLoader;
use Witals\Framework\Contracts\Container;

class ContentRendererTest extends TestCase
{
    private string $tmpDir;

    protected function setUp(): void
    {
        $this->tmpDir = sys_get_temp_dir() . '/presto_renderer_' . uniqid();
        mkdir($this->tmpDir, 0777, true);
    }

    protected function tearDown(): void
    {
        if (is_dir($this->tmpDir)) {
            array_map('unlink', glob($this->tmpDir . '/*') ?: []);
            rmdir($this->tmpDir);
        }
    }

    public function test_render_uses_context_loader_for_block_theme(): void
    {
        $loader = $this->createMock(ContextLoader::class);
        $loader->expects($this->once())
            ->method('renderTemplate')
            ->with('index', [])
            ->willReturn('<p>Hello</p>');

        $renderer = new ContentRenderer($loader, $this->createMock(Container::class), $this->tmpDir);
        $result = $renderer->render('index');

        $this->assertInstanceOf(RenderedContent::class, $result);
        $this->assertSame('<p>Hello</p>', $result->body);
        $this->assertTrue($result->complete);
    }

    public function test_render_uses_classic_engine_for_legacy_theme(): void
    {
        file_put_contents($this->tmpDir . '/style.css', '/* Theme Name: Legacy */');
        file_put_contents($this->tmpDir . '/index.php', '<?php');

        $engine = $this->createMock(ClassicThemeEngine::class);
        $engine->expects($this->once())
            ->method('render')
            ->with('index', [])
            ->willReturn(new RenderedContent('<p>Legacy</p>', '', true));

        $container = $this->createMock(Container::class);
        $container->method('has')->with(ClassicThemeEngine::class)->willReturn(true);
        $container->method('make')->with(ClassicThemeEngine::class)->willReturn($engine);

        $loader = $this->createMock(ContextLoader::class);
        $loader->expects($this->never())->method('renderTemplate');

        $renderer = new ContentRenderer($loader, $container, $this->tmpDir);
        $result = $renderer->render('index');

        $this->assertSame('<p>Legacy</p>', $result->body);
    }

    public function test_render_falls_back_to_context_loader_when_classic_unavailable(): void
    {
        file_put_contents($this->tmpDir . '/style.css', '/* Theme Name: Legacy */');
        file_put_contents($this->tmpDir . '/index.php', '<?php');

        $loader = $this->createMock(ContextLoader::class);
        $loader->method('renderTemplate')->willReturn('<p>Context</p>');

        $container = $this->createMock(Container::class);
        $container->method('has')->willReturn(false);

        $renderer = new ContentRenderer($loader, $container, $this->tmpDir);
        $result = $renderer->render('index');

        $this->assertSame('<p>Context</p>', $result->body);
    }

    public function test_supports_delegates_to_context_loader_for_block_theme(): void
    {
        $loader = $this->createMock(ContextLoader::class);
        $loader->method('supports')->with('single')->willReturn(true);

        $renderer = new ContentRenderer($loader, $this->createMock(Container::class), $this->tmpDir);

        $this->assertTrue($renderer->supports('single'));
    }

    public function test_null_renderer_returns_empty_content(): void
    {
        $renderer = new NullContentRenderer();
        $result = $renderer->render('index');

        $this->assertInstanceOf(RenderedContent::class, $result);
        $this->assertSame('', $result->body);
        $this->assertSame('', $result->styles);
    }
}
