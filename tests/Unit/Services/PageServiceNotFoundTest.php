<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use App\Services\PageService;
use App\Http\TemplateResolver;
use App\Http\Mappings\ConfigMappingPolicy;
use App\Services\HtmlComposer;
use App\Http\PageRenderer;
use App\Contracts\Http\PageRenderer as PageRendererContract;
use App\Contracts\Http\ThemeConfig;
use App\Contracts\Services\ContentRenderer;
use App\Contracts\Services\RenderedContent;
use App\Exceptions\NotFoundException;
use PrestoWorld\Modules\Schema\PostRepository;
use Cycle\Database\DatabaseInterface;
use Witals\Framework\Http\Request;

class StubPageService extends PageService
{
    /** @var array<string, array<string, mixed>> */
    public array $postsBySlug = [];

    /** @var array<string, array<string, mixed>> */
    public array $termsByPath = [];

    /** @var list<string> */
    public array $lookedUpSlugs = [];

    protected function findPostBySlug(string $slug): array
    {
        $this->lookedUpSlugs[] = $slug;

        return $this->postsBySlug[$slug] ?? [];
    }

    protected function findTerm(array $segments): ?array
    {
        return $this->termsByPath[implode('/', $segments)] ?? null;
    }
}

class PageServiceNotFoundTest extends TestCase
{
    private function makeResolver(array $mapping = ['/' => 'index'], string $default = 'index'): TemplateResolver
    {
        return new TemplateResolver(new ConfigMappingPolicy($mapping, $default));
    }

    private function makePageRenderer(): PageRendererContract
    {
        return new PageRenderer(new HtmlComposer(ThemeConfig::fromArray([
            'default_title' => 'Test',
            'css_reset' => '',
        ])));
    }

    /** @param array<array-key, bool> $supports template => supported */
    private function makeContentRenderer(array $supports = [], ?string $renderedBody = null): ContentRenderer
    {
        $renderer = $this->createMock(ContentRenderer::class);
        $renderer->method('supports')->willReturnCallback(
            fn (string $template): bool => $supports[$template] ?? false,
        );
        $renderer->method('render')->willReturnCallback(
            fn (string $template) => new RenderedContent(
                $renderedBody ?? "<main>{$template}</main>",
                '',
            ),
        );

        return $renderer;
    }

    private function makeService(
        ?TemplateResolver $resolver = null,
        ?ContentRenderer $contentRenderer = null,
        ?PageRendererContract $pageRenderer = null,
    ): StubPageService {
        return new StubPageService(
            $resolver ?? $this->makeResolver(),
            $contentRenderer ?? $this->makeContentRenderer(),
            $pageRenderer ?? $this->makePageRenderer(),
            $this->createMock(PostRepository::class),
            $this->createMock(DatabaseInterface::class),
        );
    }

    public function test_unknown_path_throws_not_found(): void
    {
        $service = $this->makeService();

        $this->expectException(NotFoundException::class);

        $service->handle(new Request('GET', '/category/esports'));
    }

    public function test_unknown_nested_path_throws_not_found(): void
    {
        $service = $this->makeService();

        $this->expectException(NotFoundException::class);

        $service->handle(new Request('GET', '/parent/child/missing'));
    }

    public function test_existing_category_term_renders_category_template(): void
    {
        $contentRenderer = $this->makeContentRenderer(['category' => true, 'archive' => true]);
        $service = $this->makeService(contentRenderer: $contentRenderer);
        $service->termsByPath['category/esports'] = ['id' => 1, 'taxonomy' => 'category', 'slug' => 'esports'];

        $html = $service->handle(new Request('GET', '/category/esports'));

        $this->assertStringContainsString('<main>category</main>', $html);
    }

    public function test_category_falls_back_to_archive_template(): void
    {
        $contentRenderer = $this->makeContentRenderer(['category' => false, 'archive' => true]);
        $service = $this->makeService(contentRenderer: $contentRenderer);
        $service->termsByPath['category/esports'] = ['id' => 1, 'taxonomy' => 'category', 'slug' => 'esports'];

        $html = $service->handle(new Request('GET', '/category/esports'));

        $this->assertStringContainsString('<main>archive</main>', $html);
    }

    public function test_archive_falls_back_to_default_template_when_unsupported(): void
    {
        $contentRenderer = $this->makeContentRenderer();
        $service = $this->makeService(contentRenderer: $contentRenderer);
        $service->termsByPath['tag/gaming'] = ['id' => 2, 'taxonomy' => 'tag', 'slug' => 'gaming'];

        $html = $service->handle(new Request('GET', '/tag/gaming'));

        $this->assertStringContainsString('<main>index</main>', $html);
    }

    public function test_existing_page_uses_page_template(): void
    {
        $contentRenderer = $this->makeContentRenderer(['page' => true]);
        $service = $this->makeService(contentRenderer: $contentRenderer);
        $service->postsBySlug['lien-he'] = [
            'post_type' => 'page',
            'title' => 'Liên hệ',
            'content' => '<p>Contact</p>',
        ];

        $html = $service->handle(new Request('GET', '/lien-he'));

        $this->assertStringContainsString('<main>page</main>', $html);
    }

    public function test_existing_post_uses_single_template(): void
    {
        $contentRenderer = $this->makeContentRenderer(['single' => true]);
        $service = $this->makeService(contentRenderer: $contentRenderer);
        $service->postsBySlug['tin-moi'] = [
            'post_type' => 'post',
            'title' => 'Tin mới',
            'content' => '<p>News</p>',
        ];

        $html = $service->handle(new Request('GET', '/tin-moi'));

        $this->assertStringContainsString('<main>single</main>', $html);
    }

    public function test_locale_prefixed_path_strips_locale_when_looking_up_content(): void
    {
        $service = $this->makeService();

        try {
            $service->handle(new Request('GET', '/vi/khong-ton-tai'));
            $this->fail('Expected NotFoundException was not thrown');
        } catch (NotFoundException) {
            $this->assertSame(['vi', 'khong-ton-tai'], $service->lookedUpSlugs);
        }
    }

    public function test_locale_prefixed_path_finds_content(): void
    {
        $contentRenderer = $this->makeContentRenderer(['page' => true]);
        $service = $this->makeService(contentRenderer: $contentRenderer);
        $service->postsBySlug['ve-chung-toi'] = [
            'post_type' => 'page',
            'title' => 'Về chúng tôi',
            'content' => '',
        ];

        $html = $service->handle(new Request('GET', '/vi/ve-chung-toi'));

        $this->assertStringContainsString('<main>page</main>', $html);
    }

    public function test_explicit_mapping_renders_without_matching_content(): void
    {
        $contentRenderer = $this->makeContentRenderer();
        $service = $this->makeService(
            resolver: $this->makeResolver(['/' => 'index', '/about' => 'page-no-title']),
            contentRenderer: $contentRenderer,
        );

        $html = $service->handle(new Request('GET', '/about'));

        $this->assertStringContainsString('<main>page-no-title</main>', $html);
    }

    public function test_render_not_found_uses_theme_404_template(): void
    {
        $contentRenderer = $this->makeContentRenderer(['404' => true], renderedBody: '<main>themed-404</main>');
        $service = $this->makeService(contentRenderer: $contentRenderer);

        $html = $service->renderNotFound(new Request('GET', '/missing'));

        $this->assertStringContainsString('<main>themed-404</main>', $html);
        $this->assertStringContainsString('<title>Page not found</title>', $html);
    }

    public function test_render_not_found_falls_back_when_theme_has_no_404_template(): void
    {
        $service = $this->makeService(contentRenderer: $this->makeContentRenderer());

        $html = $service->renderNotFound(new Request('GET', '/missing'));

        $this->assertStringContainsString('Page not found', $html);
        $this->assertStringContainsString('404', $html);
    }

    public function test_render_not_found_falls_back_when_themed_body_is_empty(): void
    {
        $contentRenderer = $this->makeContentRenderer(['404' => true], renderedBody: '');
        $service = $this->makeService(contentRenderer: $contentRenderer);

        $html = $service->renderNotFound(new Request('GET', '/missing'));

        $this->assertStringContainsString('Page not found', $html);
    }

    public function test_missing_content_tables_do_not_forge_404(): void
    {
        $db = $this->createMock(DatabaseInterface::class);
        $db->method('hasTable')->willReturn(false);

        $service = new PageService(
            $this->makeResolver(),
            $this->makeContentRenderer(),
            $this->makePageRenderer(),
            $this->createMock(PostRepository::class),
            $db,
        );

        $html = $service->handle(new Request('GET', '/category/esports'));

        $this->assertStringContainsString('<main>index</main>', $html);
    }

    public function test_lookup_failure_does_not_forge_404(): void
    {
        $db = $this->createMock(DatabaseInterface::class);
        $db->method('hasTable')->willReturn(true);
        $db->method('select')->willThrowException(new \RuntimeException('connection lost'));

        $service = new PageService(
            $this->makeResolver(),
            $this->makeContentRenderer(),
            $this->makePageRenderer(),
            $this->createMock(PostRepository::class),
            $db,
        );

        $html = $service->handle(new Request('GET', '/khong-ton-tai'));

        $this->assertStringContainsString('<main>index</main>', $html);
    }

    public function test_table_prefix_from_env_is_honored(): void
    {
        putenv('PW_TABLE_PREFIX=wp_');

        try {
            $db = $this->createMock(DatabaseInterface::class);
            $db->expects($this->once())->method('hasTable')->with('wp_posts')->willReturn(false);

            $service = new PageService(
                $this->makeResolver(),
                $this->makeContentRenderer(),
                $this->makePageRenderer(),
                $this->createMock(PostRepository::class),
                $db,
            );

            $html = $service->handle(new Request('GET', '/khong-ton-tai'));

            $this->assertStringContainsString('<main>index</main>', $html);
        } finally {
            putenv('PW_TABLE_PREFIX');
        }
    }
}
