<?php

declare(strict_types=1);

namespace Tests\Unit\Http;

use PHPUnit\Framework\TestCase;
use App\Http\Kernel;
use App\Contracts\Http\PageRenderer;
use App\Http\TemplateResolver;
use Witals\Framework\Http\Request;
use Witals\Framework\Http\Response;
use PrestoWorld\Modules\Gutenberg\Module as GutenbergModule;

class KernelTest extends TestCase
{
    public function test_handle_returns_response(): void
    {
        $gutenberg = $this->createMock(GutenbergModule::class);
        $gutenberg->method('getStyles')->willReturn('');
        $gutenberg->method('renderTemplate')->willReturn('<p>Content</p>');

        $renderer = $this->createMock(PageRenderer::class);
        $renderer->expects($this->once())->method('addStyle');
        $renderer->method('render')->willReturn('<html><body><p>Content</p></body></html>');

        $resolver = $this->createMock(TemplateResolver::class);
        $resolver->method('resolve')->willReturn('index');

        $kernel = new Kernel($gutenberg, $renderer, $resolver);
        $request = new Request('GET', '/', [], [], [], [], [], [], null);

        $response = $kernel->handle($request);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringContainsString('text/html', $response->getHeader('Content-Type'));
    }

    public function test_handle_uses_resolved_template(): void
    {
        $gutenberg = $this->createMock(GutenbergModule::class);
        $gutenberg->method('getStyles')->willReturn('');
        $gutenberg->method('renderTemplate')->willReturn('<p>Content</p>');

        $renderer = $this->createMock(PageRenderer::class);
        $renderer->method('render')->willReturn('<html><body></body></html>');

        $resolver = $this->createMock(TemplateResolver::class);
        $resolver->expects($this->once())->method('resolve')->willReturn('search');

        $kernel = new Kernel($gutenberg, $renderer, $resolver);
        $request = new Request('GET', '/search', [], [], [], [], [], [], null);

        $kernel->handle($request);
    }

    public function test_handle_injects_styles(): void
    {
        $gutenberg = $this->createMock(GutenbergModule::class);
        $gutenberg->method('getStyles')->willReturn('.test { color: red; }');
        $gutenberg->method('renderTemplate')->willReturn('');

        $renderer = $this->createMock(PageRenderer::class);
        $renderer->expects($this->once())
            ->method('addStyle')
            ->with('.test { color: red; }');
        $renderer->method('render')->willReturn('');

        $kernel = new Kernel($gutenberg, $renderer, $this->createMock(TemplateResolver::class));
        $request = new Request('GET', '/', [], [], [], [], [], [], null);

        $kernel->handle($request);
    }
}
