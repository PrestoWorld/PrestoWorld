<?php

declare(strict_types=1);

namespace Tests\Unit\Http;

use PHPUnit\Framework\TestCase;
use App\Http\PageRenderer;
use App\Contracts\Services\HtmlComposer;

class PageRendererTest extends TestCase
{
    private function makeRenderer(?HtmlComposer $composer = null): PageRenderer
    {
        return new PageRenderer($composer ?? $this->createMock(HtmlComposer::class));
    }

    public function test_add_style_appears_in_output(): void
    {
        $composer = $this->createMock(HtmlComposer::class);
        $composer->expects($this->once())
            ->method('compose')
            ->with('', "body { color: red; }", null)
            ->willReturn('<html>output</html>');

        $renderer = $this->makeRenderer($composer);
        $renderer->addStyle('body { color: red; }');
        $html = $renderer->render('');

        $this->assertSame('<html>output</html>', $html);
    }

    public function test_multiple_styles_are_combined(): void
    {
        $composer = $this->createMock(HtmlComposer::class);
        $composer->expects($this->once())
            ->method('compose')
            ->with('', "a { color: blue; }\np { margin: 0; }", null)
            ->willReturn('<html>combined</html>');

        $renderer = $this->makeRenderer($composer);
        $renderer->addStyle('a { color: blue; }');
        $renderer->addStyle('p { margin: 0; }');
        $html = $renderer->render('');

        $this->assertSame('<html>combined</html>', $html);
    }

    public function test_add_style_after_render_does_not_affect_previous_output(): void
    {
        $composer = $this->createMock(HtmlComposer::class);
        $matcher = $this->exactly(2);
        $composer->expects($matcher)
            ->method('compose')
            ->willReturnCallback(function () use ($matcher) {
                return $matcher->numberOfInvocations() === 1 ? '<html>first</html>' : '<html>second</html>';
            });

        $renderer = $this->makeRenderer($composer);
        $first = $renderer->render('');

        $renderer->addStyle('new { color: red; }');
        $second = $renderer->render('');

        $this->assertNotSame($second, $first);
    }

    public function test_render_passes_body_to_composer(): void
    {
        $composer = $this->createMock(HtmlComposer::class);
        $composer->expects($this->once())
            ->method('compose')
            ->with('<p>Content</p>', '', null);

        $renderer = $this->makeRenderer($composer);
        $renderer->render('<p>Content</p>');
    }

    public function test_render_passes_custom_title_to_composer(): void
    {
        $composer = $this->createMock(HtmlComposer::class);
        $composer->expects($this->once())
            ->method('compose')
            ->with('body', '', 'Custom Title');

        $renderer = $this->makeRenderer($composer);
        $renderer->render('body', 'Custom Title');
    }
}
