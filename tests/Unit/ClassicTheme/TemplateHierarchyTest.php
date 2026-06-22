<?php

declare(strict_types=1);

namespace Tests\Unit\ClassicTheme;

use PHPUnit\Framework\TestCase;
use PrestoWorld\Modules\ClassicTheme\TemplateHierarchy;

class TemplateHierarchyTest extends TestCase
{
    private TemplateHierarchy $hierarchy;

    protected function setUp(): void
    {
        $this->hierarchy = new TemplateHierarchy();
    }

    public function test_home_resolves(): void
    {
        $result = $this->hierarchy->resolve('home');

        $this->assertSame(['home', 'index'], $result);
    }

    public function test_index_resolves(): void
    {
        $result = $this->hierarchy->resolve('index');

        $this->assertSame(['home', 'index'], $result);
    }

    public function test_singular_without_post(): void
    {
        $result = $this->hierarchy->resolve('singular');

        $this->assertSame(['singular', 'single', 'index'], $result);
    }

    public function test_singular_with_post_type(): void
    {
        $result = $this->hierarchy->resolve('singular', ['post_type' => 'post']);

        $this->assertSame(['singular', 'single-post', 'single', 'index'], $result);
    }

    public function test_singular_with_post_id(): void
    {
        $result = $this->hierarchy->resolve('singular', ['ID' => 42]);

        $this->assertSame(['singular', 'single-42', 'single', 'index'], $result);
    }

    public function test_singular_with_type_and_id(): void
    {
        $result = $this->hierarchy->resolve('singular', ['post_type' => 'post', 'ID' => 42]);

        $this->assertSame(['singular', 'single-post', 'single-42', 'single', 'index'], $result);
    }

    public function test_page_without_post_data(): void
    {
        $result = $this->hierarchy->resolve('page');

        $this->assertSame(['page', 'singular', 'index'], $result);
    }

    public function test_page_with_slug(): void
    {
        $result = $this->hierarchy->resolve('page', ['post_name' => 'about']);

        $this->assertSame(['page-about', 'page', 'singular', 'index'], $result);
    }

    public function test_page_with_id(): void
    {
        $result = $this->hierarchy->resolve('page', ['ID' => 15]);

        $this->assertSame(['page-15', 'page', 'singular', 'index'], $result);
    }

    public function test_archive(): void
    {
        $result = $this->hierarchy->resolve('archive');

        $this->assertSame(['archive', 'index'], $result);
    }

    public function test_archive_with_post_type(): void
    {
        $result = $this->hierarchy->resolve('archive', ['post_type' => 'product']);

        $this->assertSame(['archive', 'archive-product', 'index'], $result);
    }

    public function test_search(): void
    {
        $result = $this->hierarchy->resolve('search');

        $this->assertSame(['search', 'index'], $result);
    }

    public function test_404(): void
    {
        $result = $this->hierarchy->resolve('404');

        $this->assertSame(['404', 'index'], $result);
    }

    public function test_custom_template(): void
    {
        $result = $this->hierarchy->resolve('custom-template');

        $this->assertSame(['custom-template', 'index'], $result);
    }

    public function test_resolve_template_slug_returns_first_candidate(): void
    {
        $slug = $this->hierarchy->resolveTemplateSlug('page', ['post_name' => 'about']);

        $this->assertSame('page-about', $slug);
    }

    public function test_resolve_template_slug_falls_back_to_index(): void
    {
        $slug = $this->hierarchy->resolveTemplateSlug('nonexistent');

        $this->assertSame('nonexistent', $slug);
    }

    public function test_resolve_deduplicates_candidates(): void
    {
        $result = $this->hierarchy->resolve('page', ['post_name' => 'page']);

        $this->assertSame(['page-page', 'page', 'singular', 'index'], $result);
    }

    public function test_single_accepts_custom_post_type(): void
    {
        $result = $this->hierarchy->resolve('single', ['post_type' => 'book', 'ID' => 99]);

        $this->assertSame(['singular', 'single-book', 'single-99', 'single', 'index'], $result);
    }
}
