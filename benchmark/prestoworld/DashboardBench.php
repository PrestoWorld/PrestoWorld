<?php

declare(strict_types=1);

namespace Benchmark\PrestoWorld;

use App\Foundation\Application;
use Witals\Framework\Contracts\RuntimeType;
use Witals\Framework\Http\Request;
use App\Http\Routing\Contracts\RouteRegistryInterface;
use Benchmark\Framework\Benchmark as BaseBenchmark;

class DashboardBench extends BaseBenchmark
{
    private ?Application $app = null;
    private ?RouteRegistryInterface $registry = null;

    public function run(): array
    {
        $this->ensureBooted();
        $results = [];

        $request = new Request('GET', '/dashboard');

        // 1. Dashboard dispatch (full render pipeline)
        $response = null;
        $results['dispatch_render'] = $this->measure(function () use ($request, &$response) {
            $response = $this->registry->dispatch(clone $request);
        }, 50);

        // 2. Raw SpaController invocation (no middleware)
        $controller = $this->app->make(\App\Http\Controllers\Admin\SpaController::class);
        $results['controller_invoke'] = $this->measure(function () use ($controller) {
            $controller->__invoke(new Request('GET', '/dashboard'));
        }, 200);

        // 3. API: menu endpoint
        $results['api_menu'] = $this->measure(function () use ($controller) {
            $controller->menu(new Request('GET', '/api/admin/menu'));
        }, 500);

        // 4. API: widgets endpoint
        $results['api_widgets'] = $this->measure(function () use ($controller) {
            $controller->dashboardWidgets(new Request('GET', '/api/admin/dashboard/widgets'));
        }, 500);

        // 5. Skin rendering (layout only)
        $skinManager = $this->app->make(\PrestoWorld\Modules\Admin\SkinManager::class);
        $skin = $skinManager->getActiveSkin();
        if ($skin !== null) {
            $results['skin_render_layout'] = $this->measure(function () use ($skin) {
                $skin->renderLayout('', [
                    'title' => 'Admin',
                    'initialState' => ['user' => ['name' => 'Admin'], 'menu' => [], 'widgets' => []],
                ]);
            }, 500);
        }

        // 6. Response size
        if ($response !== null) {
            $body = $response->getContent();
            $results['response_size'] = [
                'value' => strlen($body) . ' bytes',
            ];
        }

        // 7. Full handle() lifecycle for dashboard
        $results['full_handle_dashboard'] = $this->measure(function () {
            (new Application(getcwd(), RuntimeType::TRADITIONAL))
                ->handle(new Request('GET', '/dashboard'));
        }, 20);

        // 8. Middleware pipeline overhead
        $results['middleware_overhead'] = $this->measure(function () use ($request) {
            $app = new Application(getcwd(), RuntimeType::TRADITIONAL);
            $app->boot();
            $reg = $app->make(RouteRegistryInterface::class);
            $reg->dispatch(clone $request);
        }, 20);

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
