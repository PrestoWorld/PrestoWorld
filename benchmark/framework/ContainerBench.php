<?php

declare(strict_types=1);

namespace Benchmark\Framework;

use App\Foundation\Application;
use Witals\Framework\Contracts\RuntimeType;
use Witals\Framework\Http\Request;
use Witals\Framework\Http\Response;

class ContainerBench extends Benchmark
{
    private ?Application $app = null;

    public function run(): array
    {
        $this->ensureBooted();
        $results = [];

        // 1. make() existing singleton
        $results['make_singleton'] = $this->measure(
            fn() => $this->app->make(\Witals\Framework\Module\ModuleManager::class),
            5000
        );

        // 2. make() with parameters
        $results['make_with_params'] = $this->measure(
            fn() => $this->app->make(Request::class, ['method' => 'GET', 'uri' => '/test']),
            5000
        );

        // 3. make() Response (no constructor args needed)
        $results['make_response'] = $this->measure(
            fn() => $this->app->make(Response::class, ['content' => '', 'status' => 200]),
            5000
        );

        // 4. call() a closure with parameter injection
        $results['call_closure'] = $this->measure(function () {
            $this->app->call(function (Request $request) {
                return $request->path();
            }, ['request' => new Request('GET', '/test')]);
        }, 5000);

        // 5. call() a class method with injection
        $controller = $this->app->make(\App\Http\Controllers\Admin\SpaController::class);
        $results['call_method'] = $this->measure(function () use ($controller) {
            $this->app->call([$controller, 'menu'], ['request' => new Request('GET', '/api/admin/menu')]);
        }, 500);

        // 6. has() check
        $results['has_check'] = $this->measure(
            fn() => $this->app->has(\Witals\Framework\Contracts\Http\Kernel::class),
            10000
        );

        // 7. config() dot-notation lookup
        $results['config_lookup'] = $this->measure(
            fn() => $this->app->config('admin.skin'),
            10000
        );

        return $results;
    }

    private function ensureBooted(): void
    {
        if ($this->app === null) {
            $this->app = new Application(getcwd(), RuntimeType::TRADITIONAL);
            $this->app->boot();
        }
    }
}
