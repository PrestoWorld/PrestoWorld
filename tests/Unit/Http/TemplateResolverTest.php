<?php

declare(strict_types=1);

namespace Tests\Unit\Http;

use PHPUnit\Framework\TestCase;
use App\Http\TemplateResolver;
use App\Http\Mappings\ConfigMappingPolicy;
use Witals\Framework\Http\Request;

class TemplateResolverTest extends TestCase
{
    private array $defaultMapping = [
        '/' => 'index',
        '/search' => 'search',
        '/search/*' => 'search',
    ];

    private function makeResolver(?array $mapping = null, string $default = 'index'): TemplateResolver
    {
        return new TemplateResolver(
            new ConfigMappingPolicy(
                mapping: $mapping ?? $this->defaultMapping,
                defaultTemplate: $default,
            ),
        );
    }

    public function test_root_path_returns_index(): void
    {
        $resolver = $this->makeResolver();
        $this->assertSame('index', $resolver->resolve($this->request('/')));
    }

    public function test_empty_path_returns_index(): void
    {
        $resolver = $this->makeResolver();
        $this->assertSame('index', $resolver->resolve($this->request('')));
    }

    public function test_search_exact_path_returns_search(): void
    {
        $resolver = $this->makeResolver();
        $this->assertSame('search', $resolver->resolve($this->request('/search')));
    }

    public function test_search_nested_returns_search(): void
    {
        $resolver = $this->makeResolver();
        $this->assertSame('search', $resolver->resolve($this->request('/search/products')));
    }

    public function test_search_with_query_returns_search(): void
    {
        $resolver = $this->makeResolver();
        $this->assertSame('search', $resolver->resolve($this->request('/search?q=hello')));
    }

    public function test_unknown_path_falls_back_to_index(): void
    {
        $resolver = $this->makeResolver();
        $this->assertSame('index', $resolver->resolve($this->request('/about')));
    }

    public function test_unknown_path_uses_custom_default(): void
    {
        $resolver = $this->makeResolver(default: 'fallback');
        $this->assertSame('fallback', $resolver->resolve($this->request('/unknown')));
    }

    public function test_custom_mapping(): void
    {
        $resolver = $this->makeResolver([
            '/' => 'home',
            '/blog' => 'archive',
        ]);

        $this->assertSame('home', $resolver->resolve($this->request('/')));
        $this->assertSame('archive', $resolver->resolve($this->request('/blog')));
        $this->assertSame('index', $resolver->resolve($this->request('/other')));
    }

    public function test_empty_mapping_uses_default(): void
    {
        $resolver = $this->makeResolver(mapping: [], default: 'fallback');
        $this->assertSame('fallback', $resolver->resolve($this->request('/')));
        $this->assertSame('fallback', $resolver->resolve($this->request('/anything')));
    }

    public function test_wildcard_mapping(): void
    {
        $resolver = $this->makeResolver([
            '/category/*' => 'archive',
        ]);

        $this->assertSame('archive', $resolver->resolve($this->request('/category/tech')));
        $this->assertSame('archive', $resolver->resolve($this->request('/category/tech/sub')));
        $this->assertSame('index', $resolver->resolve($this->request('/other')));
    }

    public function test_resolver_accepts_custom_policy(): void
    {
        $policy = $this->createMock(\App\Contracts\Http\TemplateMappingPolicy::class);
        $policy->method('match')->willReturn('custom');

        $resolver = new TemplateResolver($policy);
        $this->assertSame('custom', $resolver->resolve($this->request('/any')));
    }

    public function test_resolver_returns_null_from_policy(): void
    {
        $policy = $this->createMock(\App\Contracts\Http\TemplateMappingPolicy::class);
        $policy->method('match')->willReturn(null);

        $resolver = new TemplateResolver($policy);
        $this->assertNull($resolver->resolve($this->request('/any')));
    }

    public function test_matches_explicitly_true_for_mapped_paths(): void
    {
        $resolver = $this->makeResolver();

        $this->assertTrue($resolver->matchesExplicitly($this->request('/')));
        $this->assertTrue($resolver->matchesExplicitly($this->request('/search')));
        $this->assertTrue($resolver->matchesExplicitly($this->request('/search/products')));
    }

    public function test_matches_explicitly_false_for_default_fallback(): void
    {
        $resolver = $this->makeResolver();

        $this->assertFalse($resolver->matchesExplicitly($this->request('/about')));
        $this->assertFalse($resolver->matchesExplicitly($this->request('/category/esports')));
    }

    public function test_matches_explicitly_false_for_empty_mapping(): void
    {
        $resolver = $this->makeResolver(mapping: []);

        $this->assertFalse($resolver->matchesExplicitly($this->request('/')));
    }

    public function test_matches_explicitly_false_for_prefix_rule_children(): void
    {
        $resolver = $this->makeResolver(mapping: ['/' => 'index', '/about' => 'page-no-title']);

        $this->assertTrue($resolver->matchesExplicitly($this->request('/about')));
        // A plain prefix rule picks the template but does not declare the URL as known.
        $this->assertFalse($resolver->matchesExplicitly($this->request('/about/team')));
    }

    private function request(string $uri): Request
    {
        return new Request('GET', $uri, [], [], [], [], [], [], null);
    }
}
