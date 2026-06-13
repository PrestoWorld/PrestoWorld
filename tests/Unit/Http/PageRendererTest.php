<?php

declare(strict_types=1);

namespace Tests\Unit\Http;

use PHPUnit\Framework\TestCase;
use App\Http\PageRenderer;
use App\Contracts\Services\HtmlComposer;
use App\Contracts\Services\RenderedContent;

class PageRendererTest extends TestCase
{
    private function makeRenderer(?HtmlComposer $composer = null): PageRenderer
    {
        return new PageRenderer($composer ?? $this->createMock(HtmlComposer::class));
    }

    private function content(string $body = '', string $styles = ''): RenderedContent
    {
        return new RenderedContent($body, $styles);
    }

    public function test_render_passes_body_and_styles_to_composer(): void
    {
        $composer = $this->createMock(HtmlComposer::class);
        $composer->expects($this->once())
            ->method('compose')
            ->with('<p>Content</p>', 'body { color: red; }', null)
            ->willReturn('<html>output</html>');

        $renderer = $this->makeRenderer($composer);
        $html = $renderer->render($this->content('<p>Content</p>', 'body { color: red; }'));

        $this->assertSame('<html>output</html>', $html);
    }

    public function test_render_passes_empty_styles_when_no_styles(): void
    {
        $composer = $this->createMock(HtmlComposer::class);
        $composer->expects($this->once())
            ->method('compose')
            ->with('', '', null);

        $renderer = $this->makeRenderer($composer);
        $renderer->render($this->content());
    }

    public function test_render_passes_custom_title_to_composer(): void
    {
        $composer = $this->createMock(HtmlComposer::class);
        $composer->expects($this->once())
            ->method('compose')
            ->with('body', '', 'Custom Title');

        $renderer = $this->makeRenderer($composer);
        $renderer->render($this->content('body'), 'Custom Title');
    }
}
