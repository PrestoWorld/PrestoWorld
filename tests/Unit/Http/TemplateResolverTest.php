<?php

declare(strict_types=1);

namespace Tests\Unit\Http;

use PHPUnit\Framework\TestCase;
use App\Http\TemplateResolver;
use Witals\Framework\Http\Request;

class TemplateResolverTest extends TestCase
{
    private TemplateResolver $resolver;

    protected function setUp(): void
    {
        $this->resolver = new TemplateResolver();
    }

    public function test_root_path_returns_index(): void
    {
        $request = $this->makeRequest('/');
        $this->assertSame('index', $this->resolver->resolve($request));
    }

    public function test_empty_path_returns_index(): void
    {
        $request = $this->makeRequest('');
        $this->assertSame('index', $this->resolver->resolve($request));
    }

    public function test_search_path_returns_search(): void
    {
        $request = $this->makeRequest('/search');
        $this->assertSame('search', $this->resolver->resolve($request));
    }

    public function test_search_with_query_returns_search(): void
    {
        $request = $this->makeRequest('/search?q=hello');
        $this->assertSame('search', $this->resolver->resolve($request));
    }

    public function test_search_nested_path_returns_search(): void
    {
        $request = $this->makeRequest('/search/products');
        $this->assertSame('search', $this->resolver->resolve($request));
    }

    public function test_unknown_path_falls_back_to_index(): void
    {
        $request = $this->makeRequest('/about');
        $this->assertSame('index', $this->resolver->resolve($request));
    }

    public function test_api_path_falls_back_to_index(): void
    {
        $request = $this->makeRequest('/api/v1/posts');
        $this->assertSame('index', $this->resolver->resolve($request));
    }

    private function makeRequest(string $uri): Request
    {
        return new Request('GET', $uri, [], [], [], [], [], [], null);
    }
}
