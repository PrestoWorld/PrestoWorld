<?php

declare(strict_types=1);

namespace Tests\Unit\Theme;

use PHPUnit\Framework\TestCase;
use PrestoWorld\Theme\BlockThemeEngineAdapter;
use PrestoWorld\Contracts\Theme\ThemeEngineInterface;
use PrestoWorld\Modules\Gutenberg\Module as GutenbergModule;
use App\Contracts\Services\RenderedContent;

class BlockThemeEngineAdapterTest extends TestCase
{
    public function test_implements_theme_engine_interface(): void
    {
        $adapter = new BlockThemeEngineAdapter($this->createMock(GutenbergModule::class));

        $this->assertInstanceOf(ThemeEngineInterface::class, $adapter);
    }

    public function test_render_delegates_to_gutenberg(): void
    {
        $gutenberg = $this->createMock(GutenbergModule::class);
        $gutenberg->method('renderTemplate')->with('index')->willReturn('<p>Block Content</p>');
        $gutenberg->method('getStyles')->willReturn('body { color: blue; }');

        $adapter = new BlockThemeEngineAdapter($gutenberg);
        $result = $adapter->render('index');

        $this->assertInstanceOf(RenderedContent::class, $result);
        $this->assertSame('<p>Block Content</p>', $result->body);
        $this->assertSame('body { color: blue; }', $result->styles);
        $this->assertFalse($result->complete);
    }

    public function test_render_passes_template_name_to_gutenberg(): void
    {
        $gutenberg = $this->createMock(GutenbergModule::class);
        $gutenberg->expects($this->once())
            ->method('renderTemplate')
            ->with('page')
            ->willReturn('<p>Page</p>');

        $adapter = new BlockThemeEngineAdapter($gutenberg);
        $adapter->render('page');
    }

    public function test_render_with_post_passes_context(): void
    {
        $gutenberg = $this->createMock(GutenbergModule::class);
        $gutenberg->method('renderTemplate')->willReturn('<p>Post</p>');

        $adapter = new BlockThemeEngineAdapter($gutenberg);
        $result = $adapter->render('single', ['ID' => 1, 'post_title' => 'Test']);

        $this->assertSame('<p>Post</p>', $result->body);
    }

    public function test_get_styles_delegates_to_gutenberg(): void
    {
        $gutenberg = $this->createMock(GutenbergModule::class);
        $gutenberg->method('getStyles')->willReturn('body { color: green; }');

        $adapter = new BlockThemeEngineAdapter($gutenberg);

        $this->assertSame('body { color: green; }', $adapter->getStyles());
    }

    public function test_get_styles_returns_empty_when_gutenberg_has_none(): void
    {
        $gutenberg = $this->createMock(GutenbergModule::class);
        $gutenberg->method('getStyles')->willReturn('');

        $adapter = new BlockThemeEngineAdapter($gutenberg);

        $this->assertSame('', $adapter->getStyles());
    }

    public function test_supports_returns_true_when_template_exists(): void
    {
        $gutenberg = $this->createMock(GutenbergModule::class);

        $adapter = new BlockThemeEngineAdapter($gutenberg);

        $this->assertFalse($adapter->supports('index'));
    }
}
