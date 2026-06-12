<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use App\Services\PageService;
use App\Services\ContentRenderer;
use App\Http\TemplateResolver;
use App\Contracts\Http\PageRenderer;
use Witals\Framework\Http\Request;
use App\Exceptions\TemplateNotFoundException;
use App\Exceptions\RenderException;

class PageServiceTest extends TestCase
{
    public function test_handle_resolves_and_renders_template(): void
    {
        $resolver = $this->createMock(TemplateResolver::class);
        $resolver->method('resolve')->willReturn('index');

        $contentRenderer = $this->createMock(ContentRenderer::class);
        $contentRenderer->method('getStyles')->willReturn('.test { color: red; }');
        $contentRenderer->method('render')->willReturn('<p>Content</p>');

        $pageRenderer = $this->createMock(PageRenderer::class);
        $pageRenderer->expects($this->once())->method('addStyle')->with('.test { color: red; }');
        $pageRenderer->method('render')->willReturn('<html><body><p>Content</p></body></html>');

        $service = new PageService($resolver, $contentRenderer, $pageRenderer);
        $request = new Request('GET', '/', [], [], [], [], [], [], null);

        $result = $service->handle($request);

        $this->assertStringContainsString('<html>', $result);
        $this->assertStringContainsString('<p>Content</p>', $result);
    }

    public function test_handle_throws_when_template_empty(): void
    {
        $resolver = $this->createMock(TemplateResolver::class);
        $resolver->method('resolve')->willReturn('');

        $service = new PageService(
            $resolver,
            $this->createMock(ContentRenderer::class),
            $this->createMock(PageRenderer::class),
        );

        $this->expectException(TemplateNotFoundException::class);

        $service->handle(new Request('GET', '/', [], [], [], [], [], [], null));
    }

    public function test_handle_passes_styles_from_renderer(): void
    {
        $resolver = $this->createMock(TemplateResolver::class);
        $resolver->method('resolve')->willReturn('index');

        $contentRenderer = $this->createMock(ContentRenderer::class);
        $contentRenderer->method('getStyles')->willReturn('body { margin: 0; }');
        $contentRenderer->method('render')->willReturn('');

        $pageRenderer = $this->createMock(PageRenderer::class);
        $pageRenderer->expects($this->once())
            ->method('addStyle')
            ->with('body { margin: 0; }');
        $pageRenderer->method('render')->willReturn('');

        $service = new PageService($resolver, $contentRenderer, $pageRenderer);
        $request = new Request('GET', '/', [], [], [], [], [], [], null);

        $service->handle($request);
    }
}
