<?php

declare(strict_types=1);

namespace Tests\Feature\Http;

use PHPUnit\Framework\TestCase;
use App\Http\Kernel;
use App\Services\PageService;
use App\Contracts\Services\ContentRenderer;
use App\Http\TemplateResolver;
use App\Http\Mappings\ConfigMappingPolicy;
use App\Http\PageRenderer;
use App\Contracts\Http\ThemeConfig;
use Psr\Log\LoggerInterface;
use Witals\Framework\Http\Request;
use Witals\Framework\Http\Response;

class KernelIntegrationTest extends TestCase
{
    private Kernel $kernel;

    protected function setUp(): void
    {
        $logger = $this->createMock(LoggerInterface::class);

        $resolver = new TemplateResolver(
            new ConfigMappingPolicy(
                mapping: [
                    '/' => 'index',
                    '/search' => 'search',
                    '/search/*' => 'search',
                ],
                defaultTemplate: 'index',
            ),
        );

        $contentRenderer = $this->createMock(ContentRenderer::class);
        $contentRenderer->method('getStyles')->willReturn('');
        $contentRenderer->method('render')->willReturnCallback(fn(string $t) => "<main>{$t}</main>");

        $pageRenderer = new PageRenderer(ThemeConfig::fromArray([
            'default_title' => 'PrestoWorld',
            'css_reset' => '',
        ]));

        $pageService = new PageService($resolver, $contentRenderer, $pageRenderer);

        $this->kernel = new Kernel($pageService, $logger);
    }

    public function test_root_route_returns_index_template(): void
    {
        $response = $this->kernel->handle(new Request('GET', '/'));

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(200, $response->getStatusCode());
        $content = $response->getContent();
        $this->assertStringContainsString('<main>index</main>', $content);
        $this->assertStringContainsString('<!DOCTYPE html>', $content);
    }

    public function test_search_route_returns_search_template(): void
    {
        $response = $this->kernel->handle(new Request('GET', '/search'));

        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringContainsString('<main>search</main>', $response->getContent());
    }

    public function test_search_nested_returns_search_template(): void
    {
        $response = $this->kernel->handle(new Request('GET', '/search/products'));

        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringContainsString('<main>search</main>', $response->getContent());
    }

    public function test_unknown_route_falls_back_to_index(): void
    {
        $response = $this->kernel->handle(new Request('GET', '/about'));

        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringContainsString('<main>index</main>', $response->getContent());
    }

    public function test_response_has_security_headers(): void
    {
        $response = $this->kernel->handle(new Request('GET', '/'));

        $this->assertStringContainsString('nosniff', $response->getHeader('X-Content-Type-Options'));
        $this->assertStringContainsString('DENY', $response->getHeader('X-Frame-Options'));
    }

    public function test_response_has_html_content_type(): void
    {
        $response = $this->kernel->handle(new Request('GET', '/'));

        $this->assertStringContainsString('text/html', $response->getHeader('Content-Type'));
    }

    public function test_renderer_includes_styles(): void
    {
        $logger = $this->createMock(LoggerInterface::class);

        $resolver = new TemplateResolver(
            new ConfigMappingPolicy(['/' => 'index'], 'index'),
        );

        $contentRenderer = $this->createMock(ContentRenderer::class);
        $contentRenderer->method('getStyles')->willReturn('body { background: red; }');
        $contentRenderer->method('render')->willReturn('<p>styled</p>');

        $pageRenderer = new PageRenderer(ThemeConfig::fromArray([
            'default_title' => 'Test',
            'css_reset' => '* { margin: 0; }',
        ]));

        $pageService = new PageService($resolver, $contentRenderer, $pageRenderer);
        $kernel = new Kernel($pageService, $logger);

        $response = $kernel->handle(new Request('GET', '/'));

        $this->assertStringContainsString('body { background: red; }', $response->getContent());
        $this->assertStringContainsString('* { margin: 0; }', $response->getContent());
    }
}
