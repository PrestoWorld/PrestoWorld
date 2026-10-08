<?php

declare(strict_types=1);

namespace Tests\Feature\Http;

use PHPUnit\Framework\TestCase;
use App\Http\Kernel;
use App\Services\PageService;
use App\Contracts\Services\ContentRenderer;
use App\Contracts\Services\RenderedContent;
use App\Http\TemplateResolver;
use App\Http\Mappings\ConfigMappingPolicy;
use App\Services\HtmlComposer;
use App\Contracts\Http\PageRenderer;
use App\Contracts\Http\ThemeConfig;
use App\Exceptions\TemplateNotFoundException;
use App\Exceptions\RenderException;
use App\Http\Routing\Contracts\RouterInterface;
use Witals\Framework\Context\Contracts\ContextManagerInterface;
use Witals\Framework\Context\Contracts\ContextLoaderInterface;
use Psr\Log\LoggerInterface;
use PrestoWorld\Modules\Schema\PostRepository;
use Cycle\Database\DatabaseInterface;
use Witals\Framework\Http\Request;
use Witals\Framework\Http\Response;

class KernelErrorHandlingTest extends TestCase
{
    public function test_template_not_found_returns_404(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('warning');
        $router = $this->createMock(RouterInterface::class);
        $router->method('dispatch')->willReturn(null);
        $contextManager = $this->createMock(ContextManagerInterface::class);
        $contextManager->method('resolveContext')->willReturn(null);
        $contextLoader = $this->createMock(ContextLoaderInterface::class);

        $resolver = $this->createMock(TemplateResolver::class);
        $resolver->method('resolve')->willReturn(null);

        $pageService = new PageService(
            $resolver,
            $this->createMock(ContentRenderer::class),
            $this->createMock(PageRenderer::class),
            $this->createMock(PostRepository::class),
            $this->createMock(DatabaseInterface::class),
        );

        $kernel = new Kernel($router, $pageService, $logger, $contextManager, $contextLoader);
        $response = $kernel->handle(new Request('GET', '/missing'));

        $this->assertSame(404, $response->getStatusCode());
        $this->assertStringContainsString('Page not found', $response->getContent());
    }

    public function test_render_error_returns_500(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('error');
        $router = $this->createMock(RouterInterface::class);
        $router->method('dispatch')->willReturn(null);
        $contextManager = $this->createMock(ContextManagerInterface::class);
        $contextManager->method('resolveContext')->willReturn(null);
        $contextLoader = $this->createMock(ContextLoaderInterface::class);

        $resolver = $this->createMock(TemplateResolver::class);
        $resolver->method('resolve')->willReturn('index');

        $contentRenderer = $this->createMock(ContentRenderer::class);
        $contentRenderer->method('render')->willThrowException(new RenderException('Broken template'));

        $pageService = new PageService(
            $resolver,
            $contentRenderer,
            $this->createMock(PageRenderer::class),
            $this->createMock(PostRepository::class),
            $this->createMock(DatabaseInterface::class),
        );

        $kernel = new Kernel($router, $pageService, $logger, $contextManager, $contextLoader);
        $response = $kernel->handle(new Request('GET', '/'));

        $this->assertSame(500, $response->getStatusCode());
        $this->assertStringContainsString('Internal server error', $response->getContent());
    }

    public function test_not_found_renders_themed_404_page(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $router = $this->createMock(RouterInterface::class);
        $router->method('dispatch')->willReturn(null);
        $contextManager = $this->createMock(ContextManagerInterface::class);
        $contextManager->method('resolveContext')->willReturn(null);
        $contextLoader = $this->createMock(ContextLoaderInterface::class);

        $resolver = new TemplateResolver(
            new ConfigMappingPolicy(mapping: ['/' => 'index'], defaultTemplate: 'index'),
        );

        $contentRenderer = $this->createMock(ContentRenderer::class);
        $contentRenderer->method('supports')->willReturnCallback(
            fn (string $template): bool => $template === '404',
        );
        $contentRenderer->method('render')->willReturn(
            new RenderedContent('<main>theme-404</main>', ''),
        );

        $composer = new HtmlComposer(ThemeConfig::fromArray([
            'default_title' => 'PrestoWorld',
            'css_reset' => '',
        ]));

        $pageService = new PageService(
            $resolver,
            $contentRenderer,
            new \App\Http\PageRenderer($composer),
            $this->createMock(PostRepository::class),
            $this->createMock(DatabaseInterface::class),
        );

        $kernel = new Kernel($router, $pageService, $logger, $contextManager, $contextLoader);
        $response = $kernel->handle(new Request('GET', '/category/esports'));

        $this->assertSame(404, $response->getStatusCode());
        $this->assertStringContainsString('<main>theme-404</main>', $response->getContent());
        $this->assertStringContainsString('<title>Page not found</title>', $response->getContent());
        $this->assertStringContainsString('text/html', $response->getHeader('Content-Type'));
    }

    public function test_custom_template_mapping(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $router = $this->createMock(RouterInterface::class);
        $router->method('dispatch')->willReturn(null);
        $contextManager = $this->createMock(ContextManagerInterface::class);
        $contextManager->method('resolveContext')->willReturn(null);
        $contextLoader = $this->createMock(ContextLoaderInterface::class);

        $resolver = new TemplateResolver(
            new ConfigMappingPolicy(
                mapping: ['/blog' => 'archive'],
                defaultTemplate: 'fallback',
            ),
        );

        $contentRenderer = $this->createMock(ContentRenderer::class);
        $contentRenderer->method('render')->willReturnCallback(fn(string $t) => new RenderedContent($t, ''));

        $pageService = new PageService(
            $resolver,
            $contentRenderer,
            $this->createMock(PageRenderer::class),
            $this->createMock(PostRepository::class),
            $this->createMock(DatabaseInterface::class),
        );

        $kernel = new Kernel($router, $pageService, $logger, $contextManager, $contextLoader);

        // Custom mapping matches
        $response = $kernel->handle(new Request('GET', '/blog'));
        $this->assertSame(200, $response->getStatusCode());

        // Unknown path renders the 404 page
        $response = $kernel->handle(new Request('GET', '/unknown'));
        $this->assertSame(404, $response->getStatusCode());
        $this->assertStringContainsString('Page not found', $response->getContent());
    }
}
