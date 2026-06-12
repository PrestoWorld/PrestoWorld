<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use App\Services\ContentRenderer;
use App\Services\NullContentRenderer;
use App\Exceptions\RenderException;
use PrestoWorld\Modules\Gutenberg\Module as GutenbergModule;

class ContentRendererTest extends TestCase
{
    public function test_render_returns_content_from_gutenberg(): void
    {
        $gutenberg = $this->createMock(GutenbergModule::class);
        $gutenberg->method('renderTemplate')->with('index')->willReturn('<p>Hello</p>');

        $renderer = new ContentRenderer($gutenberg);
        $this->assertSame('<p>Hello</p>', $renderer->render('index'));
    }

    public function test_render_throws_on_empty_result(): void
    {
        $gutenberg = $this->createMock(GutenbergModule::class);
        $gutenberg->method('renderTemplate')->willReturn('');

        $renderer = new ContentRenderer($gutenberg);

        $this->expectException(RenderException::class);
        $this->expectExceptionMessage('returned empty content');

        $renderer->render('index');
    }

    public function test_render_throws_on_null_result(): void
    {
        $gutenberg = $this->createMock(GutenbergModule::class);
        $gutenberg->method('renderTemplate')->willReturn('');

        $renderer = new ContentRenderer($gutenberg);

        $this->expectException(RenderException::class);
        $this->expectExceptionMessage('returned empty content');

        $renderer->render('index');
    }

    public function test_get_styles_returns_from_gutenberg(): void
    {
        $gutenberg = $this->createMock(GutenbergModule::class);
        $gutenberg->method('getStyles')->willReturn('body { color: red; }');

        $renderer = new ContentRenderer($gutenberg);
        $this->assertSame('body { color: red; }', $renderer->getStyles());
    }

    public function test_get_styles_returns_empty_when_no_styles(): void
    {
        $gutenberg = $this->createMock(GutenbergModule::class);
        $gutenberg->method('getStyles')->willReturn('');

        $renderer = new ContentRenderer($gutenberg);
        $this->assertSame('', $renderer->getStyles());
    }

    public function test_null_renderer_throws_on_render(): void
    {
        $renderer = new NullContentRenderer();

        $this->expectException(RenderException::class);
        $this->expectExceptionMessage('No content renderer is available');

        $renderer->render('index');
    }

    public function test_null_renderer_returns_empty_styles(): void
    {
        $renderer = new NullContentRenderer();
        $this->assertSame('', $renderer->getStyles());
    }
}
