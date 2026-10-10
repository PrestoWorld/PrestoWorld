<?php

declare(strict_types=1);

namespace App\Http;

use Psr\Log\LoggerInterface;
use Witals\Framework\Contracts\Http\Kernel as KernelContract;
use Witals\Framework\Http\Request;
use Witals\Framework\Http\Response;
use Witals\Framework\Context\Contracts\ContextManagerInterface;
use Witals\Framework\Context\Contracts\ContextLoaderInterface;
use App\Http\Routing\Contracts\RouterInterface;
use App\Services\PageService;
use App\Exceptions\TemplateNotFoundException;
use App\Exceptions\NotFoundException;
use App\Exceptions\RenderException;

class Kernel implements KernelContract
{
    private const SECURITY_HEADERS = [
        'X-Content-Type-Options' => 'nosniff',
        'X-Frame-Options' => 'DENY',
        'X-XSS-Protection' => '1; mode=block',
        'Referrer-Policy' => 'strict-origin-when-cross-origin',
        'Content-Security-Policy' => "default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com; img-src 'self' data:; connect-src 'self'; frame-ancestors 'none'; base-uri 'self'",
    ];

    public function __construct(
        private RouterInterface $router,
        private PageService $pageService,
        private LoggerInterface $logger,
        private ContextManagerInterface $contextManager,
        private ContextLoaderInterface $contextLoader,
        private ?\Witals\Framework\Application $app = null,
    ) {}

    public function handle(Request $request): Response
    {
        // 0. Ensure application is installed (redirect to /install/ if uninstalled)
        if ($this->shouldRedirectToInstaller($request)) {
            return Response::redirect('/install/');
        }

        // 1. Try router first (registered routes in routes/web.php)
        $routerResponse = $this->dispatchRouter($request);
        if ($routerResponse !== null) {
            return $routerResponse;
        }

        // 2. Try ContextLoader (context-matched pages, e.g., theme templates)
        $contextResponse = $this->dispatchContext($request);
        if ($contextResponse !== null) {
            return $contextResponse;
        }

        // 3. Fallback: theme rendering via PageService
        return $this->dispatchPageService($request);
    }

    /**
     * Check if request should be redirected to installer when not installed.
     */
    private function shouldRedirectToInstaller(Request $request): bool
    {
        $path = '/' . ltrim($request->path(), '/');

        // Allow access to installer pages, setup APIs, and static assets
        if (
            str_starts_with($path, '/install') ||
            str_starts_with($path, '/api/setup') ||
            str_starts_with($path, '/assets') ||
            str_starts_with($path, '/content') ||
            $path === '/favicon.ico'
        ) {
            return false;
        }

        return !$this->isInstalled();
    }

    /**
     * Check if PrestoWorld is installed.
     */
    private function isInstalled(): bool
    {
        if ($this->app === null) {
            return true;
        }

        try {
            /** @var \Cycle\Database\DatabaseProviderInterface $dbal */
            $dbal = $this->app->make(\Cycle\Database\DatabaseProviderInterface::class);
            $db = $dbal->database();
            $tablePrefix = getenv('PW_TABLE_PREFIX') ?: 'pw_';
            $optionsTable = $tablePrefix . 'options';

            if (!$db->hasTable($optionsTable)) {
                return false;
            }

            $row = $db->query("SELECT option_value FROM {$optionsTable} WHERE option_name = ?", ['presto_installed'])->fetch();

            return $row && $row['option_value'] === '1';
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Dispatch request through the router.
     * Returns null if no route matched (404 from router = no match).
     */
    private function dispatchRouter(Request $request): ?Response
    {
        try {
            $result = $this->router->dispatch($request);

            // Router returns a 404 JSON when no route matches — treat as "no match"
            if ($result instanceof Response) {
                if ($result->getStatusCode() === 404) {
                    return null;
                }
                return $this->withSecurityHeaders($result);
            }

            // If result is a plain string, wrap it as HTML response
            if (is_string($result)) {
                return $this->withSecurityHeaders(
                    new Response($result, 200, ['Content-Type' => 'text/html; charset=utf-8'])
                );
            }

            return null;
        } catch (\Throwable $e) {
            $this->logger->error('Kernel: Router dispatch error: {message}', [
                'message' => $e->getMessage(),
                'exception' => $e,
            ]);
            return new Response('Internal server error', 500, self::SECURITY_HEADERS);
        }
    }

    /**
     * Dispatch via ContextLoader if a context matches the request.
     */
    private function dispatchContext(Request $request): ?Response
    {
        try {
            $context = $this->contextManager->resolveContext($request);
            if ($context === null) {
                return null;
            }

            $response = $this->contextLoader->load($context);
            return $this->withSecurityHeaders($response);
        } catch (\Throwable $e) {
            $this->logger->error('Kernel: Context dispatch error: {message}', [
                'message' => $e->getMessage(),
                'exception' => $e,
            ]);
            return null;
        }
    }

    /**
     * Fallback to theme rendering via PageService.
     */
    private function dispatchPageService(Request $request): Response
    {
        try {
            $html = $this->pageService->handle($request);

            return new Response($html, 200, [
                'Content-Type' => 'text/html; charset=utf-8',
                ...self::SECURITY_HEADERS,
            ]);
        } catch (TemplateNotFoundException | NotFoundException $e) {
            $this->logger->warning('Kernel: Page not found: {path} ({reason})', [
                'path' => $request->path(),
                'reason' => $e->getMessage(),
            ]);
            return new Response($this->pageService->renderNotFound($request), 404, [
                'Content-Type' => 'text/html; charset=utf-8',
                ...self::SECURITY_HEADERS,
            ]);
        } catch (RenderException $e) {
            $this->logger->error('Kernel: Render error: {message}', [
                'message' => $e->getMessage(),
                'exception' => $e,
            ]);
            return new Response('Internal server error', 500, self::SECURITY_HEADERS);
        }
    }

    private function withSecurityHeaders(Response $response): Response
    {
        foreach (self::SECURITY_HEADERS as $name => $value) {
            $response = $response->withHeader($name, $value);
        }
        return $response;
    }
}
