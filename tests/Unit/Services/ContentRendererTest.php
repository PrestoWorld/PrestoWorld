<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use App\Services\ContentRenderer;
use App\Services\NullContentRenderer;
use App\Contracts\Services\RenderedContent;
use PrestoWorld\Modules\Gutenberg\Module as GutenbergModule;

class ContentRendererTest extends TestCase
{
    public function test_render_returns_content_and_styles_from_gutenberg(): void
    {
        $gutenberg = $this->createMock(GutenbergModule::class);
        $gutenberg->method('renderTemplate')->with('index')->willReturn('<p>Hello</p>');
        $gutenberg->method('getStyles')->willReturn('body { color: red; }');

        $renderer = new ContentRenderer($gutenberg);
        $result = $renderer->render('index');

        $this->assertInstanceOf(RenderedContent::class, $result);
        $this->assertSame('<p>Hello</p>', $result->body);
        $this->assertSame('body { color: red; }', $result->styles);
    }

    public function test_render_returns_empty_body_when_gutenberg_returns_empty(): void
    {
        $gutenberg = $this->createMock(GutenbergModule::class);
        $gutenberg->method('renderTemplate')->willReturn('');

        $renderer = new ContentRenderer($gutenberg);
        $result = $renderer->render('index');

        $this->assertSame('', $result->body);
    }

    public function test_render_returns_styles_when_gutenberg_has_styles(): void
    {
        $gutenberg = $this->createMock(GutenbergModule::class);
        $gutenberg->method('renderTemplate')->willReturn('<p>Content</p>');
        $gutenberg->method('getStyles')->willReturn('body { color: red; }');

        $renderer = new ContentRenderer($gutenberg);
        $result = $renderer->render('index');

        $this->assertSame('body { color: red; }', $result->styles);
    }

    public function test_render_returns_empty_styles_when_gutenberg_has_none(): void
    {
        $gutenberg = $this->createMock(GutenbergModule::class);
        $gutenberg->method('renderTemplate')->willReturn('<p>Content</p>');
        $gutenberg->method('getStyles')->willReturn('');

        $renderer = new ContentRenderer($gutenberg);
        $result = $renderer->render('index');

        $this->assertSame('', $result->styles);
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
