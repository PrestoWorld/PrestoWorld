<?php

declare(strict_types=1);

namespace Tests\Feature\Http;

use PHPUnit\Framework\TestCase;
use App\Http\Kernel;
use App\Services\PageService;
use App\Services\NullContentRenderer;
use App\Http\TemplateResolver;
use App\Http\Mappings\ConfigMappingPolicy;
use App\Http\PageRenderer;
use App\Services\HtmlComposer;
use App\Contracts\Http\ThemeConfig;
use Psr\Log\LoggerInterface;
use Witals\Framework\Http\Request;

class NullContentRendererTest extends TestCase
{
    public function test_handles_request_when_no_content_renderer_available(): void
    {
        $logger = $this->createMock(LoggerInterface::class);

        $resolver = new TemplateResolver(
            new ConfigMappingPolicy(
                mapping: ['/' => 'index'],
                defaultTemplate: 'index',
            ),
        );

        $contentRenderer = new NullContentRenderer();

        $composer = new HtmlComposer(ThemeConfig::fromArray([
            'default_title' => 'Test',
            'css_reset' => '',
        ]));
        $pageRenderer = new PageRenderer($composer);

        $pageService = new PageService($resolver, $contentRenderer, $pageRenderer);

        $kernel = new Kernel($pageService, $logger);
        $response = $kernel->handle(new Request('GET', '/'));

        $this->assertSame(200, $response->getStatusCode());
        $html = $response->getContent();
        $this->assertStringContainsString('<!DOCTYPE html>', $html);
        $this->assertStringContainsString('<body>', $html);
        $this->assertStringContainsString('</body>', $html);
    }

    public function test_no_styles_when_content_renderer_empty(): void
    {
        $logger = $this->createMock(LoggerInterface::class);

        $resolver = new TemplateResolver(
            new ConfigMappingPolicy(['/' => 'index'], 'index'),
        );

        $contentRenderer = new NullContentRenderer();

        $composer = new HtmlComposer(ThemeConfig::fromArray([
            'default_title' => 'Test',
            'css_reset' => 'body { margin: 0; }',
        ]));
        $pageRenderer = new PageRenderer($composer);

        $pageService = new PageService($resolver, $contentRenderer, $pageRenderer);

        $kernel = new Kernel($pageService, $logger);
        $response = $kernel->handle(new Request('GET', '/'));

        $html = $response->getContent();
        $this->assertStringContainsString('body { margin: 0; }', $html);
        $this->assertStringNotContainsString('<style></style>', $html);
    }
}
