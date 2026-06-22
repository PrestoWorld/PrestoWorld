<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use App\Services\ContentRenderer;
use App\Services\NullContentRenderer;
use App\Contracts\Services\RenderedContent;
use PrestoWorld\Theme\ThemeEngineFactory;
use PrestoWorld\Contracts\Theme\ThemeEngineInterface;

class ContentRendererTest extends TestCase
{
    private function mockEngine(): ThemeEngineInterface
    {
        return $this->createMock(ThemeEngineInterface::class);
    }

    private function mockFactory(?ThemeEngineInterface $engine = null): ThemeEngineFactory
    {
        $factory = $this->createMock(ThemeEngineFactory::class);
        $factory->method('create')->willReturn($engine ?? $this->mockEngine());

        return $factory;
    }

    public function test_render_returns_content_and_styles_from_engine(): void
    {
        $engine = $this->mockEngine();
        $engine->method('render')->with('index', [])->willReturn(
            new RenderedContent('<p>Hello</p>', 'body { color: red; }'),
        );

        $renderer = new ContentRenderer($this->mockFactory($engine));
        $result = $renderer->render('index');

        $this->assertInstanceOf(RenderedContent::class, $result);
        $this->assertSame('<p>Hello</p>', $result->body);
        $this->assertSame('body { color: red; }', $result->styles);
    }

    public function test_render_returns_empty_body_when_engine_returns_empty(): void
    {
        $engine = $this->mockEngine();
        $engine->method('render')->willReturn(new RenderedContent('', ''));

        $renderer = new ContentRenderer($this->mockFactory($engine));
        $result = $renderer->render('index');

        $this->assertSame('', $result->body);
    }

    public function test_render_passes_post_data_to_engine(): void
    {
        $post = ['ID' => 1, 'post_title' => 'Test'];
        $engine = $this->mockEngine();
        $engine->expects($this->once())
            ->method('render')
            ->with('single', $post)
            ->willReturn(new RenderedContent('<p>Test</p>', ''));

        $renderer = new ContentRenderer($this->mockFactory($engine));
        $result = $renderer->render('single', $post);

        $this->assertSame('<p>Test</p>', $result->body);
    }

    public function test_render_uses_factory_to_get_engine(): void
    {
        $engine = $this->mockEngine();
        $engine->method('render')->willReturn(new RenderedContent('<p>Engine</p>', ''));

        $factory = $this->createMock(ThemeEngineFactory::class);
        $factory->expects($this->once())->method('create')->willReturn($engine);

        $renderer = new ContentRenderer($factory);
        $result = $renderer->render('index');

        $this->assertSame('<p>Engine</p>', $result->body);
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
