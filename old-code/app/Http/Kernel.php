<?php

declare(strict_types=1);

namespace App\Http;

use Witals\Framework\Application;
use Witals\Framework\Contracts\Http\Kernel as KernelContract;
use Witals\Framework\Http\Request;
use Witals\Framework\Http\Response;
use Psr\Log\LoggerInterface;

class Kernel implements KernelContract
{
    protected Application $app;
    protected LoggerInterface $logger;

    public function __construct(Application $app, LoggerInterface $logger)
    {
        $this->app = $app;
        $this->logger = $logger;
    }

    protected array $middleware = [
        \App\Http\Middleware\LocaleMiddleware::class,
        \Witals\Framework\Auth\Middleware\AuthMiddleware::class,
        \App\Http\Middleware\AdminAuthMiddleware::class,
    ];

    public function handle(Request $request): Response
    {
        if ($this->app->has(\App\Foundation\Debug\DebugBar::class)) {
            $this->app->make(\App\Foundation\Debug\DebugBar::class)->reset();
        }

        $this->app->instance(Request::class, $request);

        $this->logger->info("Incoming request: {method} {uri}", [
            'method' => $request->method(),
            'uri' => $request->uri(),
            'ip' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
        ]);

        $pipeline = $this->middleware;

        $pipeline[] = function ($request) {
            return $this->dispatchToRouter($request);
        };

        return $this->sendRequestThroughPipeline($request, $pipeline);
    }

    protected function sendRequestThroughPipeline(Request $request, array $pipeline): Response
    {
        $middleware = array_shift($pipeline);

        if ($middleware === null) {
            throw new \RuntimeException("Middleware pipeline exhausted without response");
        }

        $next = function ($nextRequest) use ($pipeline) {
            return $this->sendRequestThroughPipeline($nextRequest, $pipeline);
        };

        if ($middleware instanceof \Closure) {
            return $middleware($request, $next);
        }

        if (is_string($middleware)) {
            $instance = $this->app->make($middleware);
            if (method_exists($instance, 'handle')) {
                return $instance->handle($request, $next);
            }
        }

        throw new \RuntimeException("Invalid middleware: " . json_encode($middleware));
    }

    protected function dispatchToRouter(Request $request): Response
    {
        try {
            $router = $this->app->make(\App\Http\Routing\Router::class);
            $result = $router->dispatch($request);

            if ($result instanceof Response) {
                $response = $result;
            } else {
                $response = Response::html((string)$result);
            }

            if ($this->shouldEnforceTemplateEngine($request, $response)) {
                if (!$this->app->has('view.rendered') || $this->app->make('view.rendered') !== true) {
                    throw new \RuntimeException("PrestoWorld Standards Error: Web response must be rendered via a registered template engine (Stempler).");
                }
            }

            return $this->injectDebugBar($request, $response);

        } catch (\Throwable $e) {
            $this->logger->error("Request error: " . $e->getMessage(), ['exception' => $e]);

            $handler = $this->app->make(\Witals\Framework\Contracts\Exceptions\ExceptionHandlerInterface::class);
            return $handler->render($e, $request);
        }
    }

    protected function injectDebugBar(Request $request, Response $response): Response
    {
        if ($response->getStatusCode() >= 300 && $response->getStatusCode() < 400) {
            return $response;
        }

        if (!env('APP_DEBUG_BAR', false) || !$this->app->has(\App\Foundation\Debug\DebugBar::class)) {
            return $response;
        }

        $content = $response->getContent();
        if (!is_string($content) || !str_contains($response->getHeader('Content-Type', ''), 'text/html')) {
            return $response;
        }

        $debugBar = $this->app->make(\App\Foundation\Debug\DebugBar::class);
        $debugBarHtml = $debugBar->render();

        if (str_contains($content, '</body>')) {
            $content = str_replace('</body>', $debugBarHtml . '</body>', $content);
        } else {
            $content .= $debugBarHtml;
        }

        return new Response($content, $response->getStatusCode(), $response->getHeaders());
    }

    protected function shouldEnforceTemplateEngine(Request $request, Response $response): bool
    {
        if (!str_contains($response->getHeader('Content-Type', ''), 'text/html')) {
            return false;
        }

        $isAjax = $request->header('X-Requested-With') === 'XMLHttpRequest';
        if ($isAjax || str_contains($request->header('Accept', ''), 'application/json')) {
            return false;
        }

        if ($response->getStatusCode() >= 300 && $response->getStatusCode() < 400) {
            return false;
        }

        return true;
    }

    public function handleHome(Request $request): Response
    {
        $modules = [];
        if (app()->has(\App\Foundation\Module\ModuleManager::class)) {
            $manager = app(\App\Foundation\Module\ModuleManager::class);
            foreach ($manager->all() as $module) {
                $modules[] = [
                    'name' => $module->getName(),
                    'version' => $module->getVersion(),
                    'priority' => $module->getPriority(),
                    'enabled' => $module->isEnabled() ? 'Yes' : 'No',
                    'loaded' => $manager->isLoaded($module->getName()) ? 'Yes' : 'No',
                    'path' => $module->getPath(),
                    'type' => $module->getType(),
                ];
            }
        }

        $postsData = [];
        $postsError = null;
        try {
            if ($this->app->has(\Cycle\ORM\ORMInterface::class)) {
                $orm = $this->app->make(\Cycle\ORM\ORMInterface::class);
                $repo = $orm->getRepository(\App\Models\Post::class);

                $posts = $repo->select()
                    ->where('status', 'publish')
                    ->where('type', 'in', ['post', 'page'])
                    ->orderBy('date', 'DESC')
                    ->limit(5)
                    ->fetchAll();

                foreach ($posts as $post) {
                    $postsData[] = [
                        'id' => $post->id,
                        'title' => $post->title,
                        'type' => $post->type,
                        'slug' => $post->slug,
                        'url' => get_permalink($post),
                        'date' => $post->date->format('Y-m-d H:i:s'),
                    ];
                }
            } else {
                $postsError = 'ORM Not Configured';
            }
        } catch (\Throwable $e) {
            $postsError = 'ORM Error: ' . $e->getMessage();
        }

        $webServices = [];
        try {
            if ($this->app->has(\Cycle\Database\DatabaseProviderInterface::class)) {
                $db = $this->app->make(\Cycle\Database\DatabaseProviderInterface::class)->database();
                $webServices = $db->select('*')
                    ->from('presto_web_services')
                    ->where('status', 'active')
                    ->limit(4)
                    ->run()
                    ->fetchAll();
            }
        } catch (\Throwable $e) {
            error_log("Home WebServices Error: " . $e->getMessage());
        }

        if (str_contains($request->header('accept', ''), 'text/html') || !$request->header('accept')) {
            $themeManager = $this->app->make(\PrestoWorld\Theme\ThemeManager::class);
            $hooks = $this->app->make('hooks');

            $hooks->doAction('pre_render_home');

            $pageTitle = $hooks->applyFilters('home_page_title', 'Home');

            if ($targetTheme = $request->query('theme')) {
                $themeManager->setActiveTheme($targetTheme);
            }
            $themeManager->loadActiveTheme();
            $this->app->instance('view.rendered', true);
            $html = $themeManager->render('index', [
                'title' => $pageTitle,
                'posts' => $postsData,
                'web_services' => $webServices,
                'posts_error' => $postsError,
                'themes' => $themeManager->all()
            ]);

            $html = $hooks->applyFilters('home_page_content', $html);

            $html = $hooks->applyFilters('presto.response_body', $html);

            return \Witals\Framework\Http\Response::html($html);
        }

        $themesInfo = [];
        $themeManager = $this->app->make(\PrestoWorld\Theme\ThemeManager::class);
        foreach ($themeManager->all() as $theme) {
            $themesInfo[] = [
                'name' => $theme->getName(),
                'title' => $theme->getTitle(),
                'type' => $theme->getType(),
                'active' => $theme->isActive()
            ];
        }

        return Response::json([
            'message' => 'Welcome to PrestoWorld Native!',
            'runtime' => $this->getEnvironmentName(),
            'modules' => $modules,
            'wordpress_enabled' => config('modules.enabled.wordpress') ? 'Yes' : 'No',
            'latest_posts' => $postsData,
            'available_themes' => $themesInfo
        ]);
    }

    public function handleHealth(Request $request): Response
    {
        return Response::json([
            'status' => 'healthy',
            'environment' => $this->getEnvironmentName(),
            'timestamp' => time(),
            'uptime' => $this->getUptime(),
        ]);
    }

    public function handleInfo(Request $request): Response
    {
        return Response::json([
            'app' => [
                'name' => 'Witals Framework',
                'environment' => $this->getEnvironmentName(),
                'is_roadrunner' => $this->app->isRoadRunner(),
                'database' => $this->checkDatabase(),
                'db_user' => env('DB_USERNAME', 'unknown'),
                'db_prefix' => env('WP_TABLE_PREFIX', 'unknown'),
                'auth_key_sample' => substr(env('WP_AUTH_KEY', 'none'), 0, 10) . '...',
            ],
            'php' => [
                'version' => PHP_VERSION,
                'sapi' => PHP_SAPI,
                'memory_limit' => ini_get('memory_limit'),
                'max_execution_time' => ini_get('max_execution_time'),
            ],
            'server' => [
                'software' => $this->getServerInfo(),
                'protocol' => $_SERVER['SERVER_PROTOCOL'] ?? 'Unknown',
            ],
            'performance' => [
                'memory_usage' => $this->getMemoryUsage(),
                'peak_memory' => $this->getPeakMemory(),
                'uptime' => $this->getUptime(),
            ],
        ]);
    }

    protected function checkDatabase(): string
    {
        try {
            if (!$this->app->has(\Cycle\Database\DatabaseProviderInterface::class)) {
                return 'Not Configured';
            }
            $dbal = $this->app->make(\Cycle\Database\DatabaseProviderInterface::class);
            $db = $dbal->database();
            $driver = $db->getDriver();
            $driver->connect();
            return 'Connected (' . get_class($driver) . ')';
        } catch (\Throwable $e) {
            return 'Error: ' . $e->getMessage();
        }
    }

    protected function getEnvironmentName(): string
    {
        if ($this->app->isRoadRunner()) return 'RoadRunner';
        if ($this->app->isReactPhp()) return 'ReactPHP';
        if ($this->app->isSwoole()) return 'Swoole';
        if ($this->app->isOpenSwoole()) return 'OpenSwoole';

        return 'Traditional Web Server';
    }

    protected function getServerInfo(): string
    {
        if ($this->app->isRoadRunner()) return 'RoadRunner';
        if ($this->app->isReactPhp()) return 'ReactPHP (Event Loop)';
        if ($this->app->isSwoole()) return 'Swoole Server';
        if ($this->app->isOpenSwoole()) return 'OpenSwoole Server';

        return $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown SAPI';
    }

    protected function getMemoryUsage(): string
    {
        return $this->formatBytes(memory_get_usage(true));
    }

    protected function getPeakMemory(): string
    {
        return $this->formatBytes(memory_get_peak_usage(true));
    }

    protected function getUptime(): string
    {
        if (!defined('WITALS_START')) {
            return 'N/A';
        }
        $uptime = microtime(true) - WITALS_START;
        return number_format($uptime, 3) . 's';
    }

    protected function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        return round($bytes, 2) . ' ' . $units[$i];
    }
}
