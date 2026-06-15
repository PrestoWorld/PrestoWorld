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
use App\Contracts\Http\PageRenderer;
use App\Contracts\Http\ThemeConfig;
use App\Exceptions\TemplateNotFoundException;
use App\Exceptions\RenderException;
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

        $resolver = $this->createMock(TemplateResolver::class);
        $resolver->method('resolve')->willReturn(null);

        $pageService = new PageService(
            $resolver,
            $this->createMock(ContentRenderer::class),
            $this->createMock(PageRenderer::class),
            $this->createMock(PostRepository::class),
            $this->createMock(DatabaseInterface::class),
        );

        $kernel = new Kernel($pageService, $logger);
        $response = $kernel->handle(new Request('GET', '/missing'));

        $this->assertSame(404, $response->getStatusCode());
        $this->assertStringContainsString('Page not found', $response->getContent());
    }

    public function test_render_error_returns_500(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('error');

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

        $kernel = new Kernel($pageService, $logger);
        $response = $kernel->handle(new Request('GET', '/'));

        $this->assertSame(500, $response->getStatusCode());
        $this->assertStringContainsString('Internal server error', $response->getContent());
    }

    public function test_custom_template_mapping(): void
    {
        $logger = $this->createMock(LoggerInterface::class);

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

        $kernel = new Kernel($pageService, $logger);

        // Custom mapping matches
        $response = $kernel->handle(new Request('GET', '/blog'));
        $this->assertSame(200, $response->getStatusCode());

        // Unknown path uses configured fallback
        $response = $kernel->handle(new Request('GET', '/unknown'));
        $this->assertSame(200, $response->getStatusCode());
    }
}
