<?php

declare(strict_types=1);

namespace Benchmark\Framework;

use App\Foundation\Application;
use Witals\Framework\Contracts\RuntimeType;
use Witals\Framework\Http\Request;
use Witals\Framework\Http\Response;
use App\Http\Routing\Contracts\RouteRegistryInterface;

class RouterBench extends Benchmark
{
    private ?Application $app = null;
    private ?RouteRegistryInterface $registry = null;

    public function run(): array
    {
        $this->ensureBooted();
        $results = [];

        $request = new Request('GET', '/dashboard');
        $apiRequest = new Request('GET', '/api/admin/menu');
        $rootRequest = new Request('GET', '/');
        $notFound = new Request('GET', '/nonexistent/page');

        // 1. Static route match — /dashboard
        $results['match_dashboard'] = $this->measure(
            fn() => $this->registry->match($request),
            5000
        );

        // 2. Static route match — API
        $results['match_api'] = $this->measure(
            fn() => $this->registry->match($apiRequest),
            5000
        );

        // 3. No-match (root)
        $results['match_root_nomatch'] = $this->measure(
            fn() => $this->registry->match($rootRequest),
            5000
        );

        // 4. Dispatch (with middleware pipeline)
        $results['dispatch_with_middleware'] = $this->measure(
            fn() => $this->registry->dispatch(clone $request),
            200
        );

        // 5. Route not found (returns null)
        $results['dispatch_nomatch'] = $this->measure(
            fn() => $this->registry->match($notFound),
            5000
        );

        // 6. Full handle() lifecycle
        $results['full_handle'] = $this->measure(
            fn() => (new Application(getcwd(), RuntimeType::TRADITIONAL))->handle(clone $request),
            20
        );

        return $results;
    }

    private function ensureBooted(): void
    {
        if ($this->app === null) {
            $this->app = new Application(getcwd(), RuntimeType::TRADITIONAL);
            $this->app->boot();
            $this->registry = $this->app->make(RouteRegistryInterface::class);
        }
    }
}
