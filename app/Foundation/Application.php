<?php

declare(strict_types=1);

namespace App\Foundation;

use Witals\Framework\Application as BaseApplication;
use PrestoWorld\Foundation\Config\ConfigRepository;
use \Psr\Log\LoggerInterface;
use Witals\Framework\Log\Drivers\StandardLogger;
use Witals\Framework\Http\Request;
use Witals\Framework\Http\Response;

class Application extends BaseApplication
{
    protected ?ConfigRepository $configRepository = null;
    protected bool $sessionGlobalsBridgeRegistered = false;

    /**
     * Force canonical HTTPS for EVERY request (routed AND frontend fallback).
     *
     * The auth cookie is Secure-flagged, so browsers only send it over https.
     * A http:// visit would silently drop it → user looks logged-out → no admin
     * bar. This must run here (not only as a RouteRegistry middleware) because
     * frontend URLs with no explicit route (/, /hello-world, …) fall through to
     * the HTTP Kernel and BYPASS the middleware pipeline entirely.
     *
     * Disable with env PW_FORCE_HTTPS=false (pure-http dev/CI).
     */
    public function handle(Request $request): Response
    {
        $redirect = $this->httpsRedirectResponse($request);
        if ($redirect !== null) {
            return $redirect;
        }

        if ($this->isLongRunning() && !$this->sessionGlobalsBridgeRegistered) {
            $this->sessionGlobalsBridgeRegistered = true;
            $this->beforeRequest(function (Request $r): void {
                $this->syncSessionGlobals($r);
            });
        }

        $response = parent::handle($request);

        if ($this->isLongRunning()) {
            $response = $this->attachSessionCookie($response);
        }

        return $response;
    }

    protected function httpsRedirectResponse(Request $request): ?Response
    {
        $enabled = getenv('PW_FORCE_HTTPS');
        if ($enabled !== false && strtolower((string) $enabled) === 'false') {
            return null;
        }

        // Behind a TLS-terminating proxy that already marks the request https.
        $forwarded = strtolower((string) ($request->header('X-Forwarded-Proto') ?? ''));
        if ($forwarded === 'https') {
            return null;
        }

        // Direct https connection (PHP sets HTTPS=on on port 443).
        $https = $_SERVER['HTTPS'] ?? '';
        if (is_string($https) && $https !== '' && strtolower($https) !== 'off') {
            return null;
        }

        $host = (string) ($request->header('Host') ?? ($_SERVER['HTTP_HOST'] ?? ''));
        if ($host === '') {
            return null;
        }

        return Response::html('', 302, [
            'Location' => 'https://' . $host . $request->uri(),
            'Cache-Control' => 'no-store',
        ]);
    }

    public function registerCoreContainerAliases(): void
    {
        parent::registerCoreContainerAliases();

        $this->instance(self::class, $this);
    }

    private function syncSessionGlobals(Request $request): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        $cookies = [];

        $raw = $request->header('Cookie') ?? $request->header('cookie') ?? $request->header('COOKIE');
        if (is_string($raw) && $raw !== '') {
            foreach (explode(';', $raw) as $pair) {
                $pair = trim($pair);
                if ($pair === '' || !str_contains($pair, '=')) {
                    continue;
                }
                [$name, $value] = explode('=', $pair, 2);
                $cookies[trim($name)] = urldecode(trim($value));
            }
        }

        $sessionCookie = $request->cookie(session_name());
        if (is_string($sessionCookie) && $sessionCookie !== '') {
            $cookies[session_name()] = $sessionCookie;
        }

        $_COOKIE = $cookies;
    }

    private function attachSessionCookie(Response $response): Response
    {
        if (session_status() !== PHP_SESSION_ACTIVE || !ini_get('session.use_cookies')) {
            return $response;
        }

        $params = session_get_cookie_params();
        $cookie = session_name() . '=' . session_id()
            . '; path=' . ($params['path'] !== '' ? $params['path'] : '/');

        if (!empty($params['domain'])) {
            $cookie .= '; domain=' . $params['domain'];
        }
        if (!empty($params['secure'])) {
            $cookie .= '; secure';
        }
        if (!empty($params['httponly'])) {
            $cookie .= '; HttpOnly';
        }
        if (!empty($params['samesite'])) {
            $cookie .= '; SameSite=' . $params['samesite'];
        }

        $existing = $response->getHeader('Set-Cookie');
        if ($existing === null) {
            $merged = [$cookie];
        } elseif (is_array($existing)) {
            $merged = array_merge($existing, [$cookie]);
        } else {
            $merged = [$existing, $cookie];
        }

        $headers = $response->getHeaders();
        $headers['Set-Cookie'] = $merged;

        return new Response($response->getContent(), $response->getStatusCode(), $headers);
    }

    public function registerConfiguredProviders(): void
    {
        parent::registerConfiguredProviders();

        // Register routing — binds RouteRegistry, Router and loads routes/web.php
        $this->register(\App\Providers\RouteServiceProvider::class);

        $this->singleton(
            \Witals\Framework\Contracts\Http\Kernel::class,
            \App\Http\Kernel::class
        );

        $this->singleton(
            \App\Contracts\Http\PageRenderer::class,
            \App\Http\PageRenderer::class,
        );

        $this->singleton(
            \App\Contracts\Services\HtmlComposer::class,
            \App\Services\HtmlComposer::class,
        );

        $this->singleton(\App\Contracts\Http\ThemeConfig::class, function () {
            return \App\Contracts\Http\ThemeConfig::fromArray($this->config('theme', []));
        });

        $this->singleton(\App\Http\TemplateResolver::class, function ($app) {
            $mapping = $app->config('templates.mapping', []);
            $default = $app->config('templates.default', 'index');
            return new \App\Http\TemplateResolver(
                new \App\Http\Mappings\ConfigMappingPolicy($mapping, $default),
            );
        });

        $this->singleton(
            \App\Contracts\Services\ContentRenderer::class,
            function ($app) {
                if ($app->has(\Presto\Context\Loader::class)) {
                    return new \App\Services\ContentRenderer(
                        $app->make(\Presto\Context\Loader::class),
                        $app,
                        self::resolveThemePath($app),
                    );
                }
                return new \App\Services\NullContentRenderer();
            },
        );

        $this->singleton(
            \App\Services\PageService::class,
        );

        // Config Manager — reads from WordPress config or environment
        $this->singleton(\App\Config\ConfigManager::class, function ($app) {
            return new \App\Config\ConfigManager($app->basePath());
        });

        // Smart URL routing — fast content type detection
        $this->singleton(\App\Http\UrlParser::class, function ($app) {
            return new \App\Http\UrlParser(
                taxonomyLoader: function () use ($app) {
                    $db = $app->make(\Cycle\Database\DatabaseInterface::class);
                    $prefix = getenv('PW_TABLE_PREFIX') ?: 'pw_';
                    $table = $prefix . 'terms';
                    if (!$db->hasTable($table)) {
                        return [];
                    }
                    try {
                        $rows = $db->select('taxonomy')->from($table)->distinct()->fetchAll();
                        return array_values(array_filter(array_column($rows, 'taxonomy')));
                    } catch (\Throwable) {
                        return [];
                    }
                },
                postTypeLoader: function () use ($app) {
                    $db = $app->make(\Cycle\Database\DatabaseInterface::class);
                    $prefix = getenv('PW_TABLE_PREFIX') ?: 'pw_';
                    $table = $prefix . 'posts';
                    if (!$db->hasTable($table)) {
                        return [];
                    }
                    try {
                        $rows = $db->select('post_type')->from($table)->distinct()->fetchAll();
                        return array_values(array_filter(array_column($rows, 'post_type'), fn ($t) => $t !== 'page'));
                    } catch (\Throwable) {
                        return [];
                    }
                },
                pageSlugLoader: function () use ($app) {
                    $db = $app->make(\Cycle\Database\DatabaseInterface::class);
                    $prefix = getenv('PW_TABLE_PREFIX') ?: 'pw_';
                    $table = $prefix . 'posts';
                    if (!$db->hasTable($table)) {
                        return [];
                    }
                    try {
                        $rows = $db->select('slug')->from($table)
                            ->where('post_type', 'page')
                            ->where('status', 'publish')
                            ->fetchAll();
                        return array_values(array_filter(array_column($rows, 'slug')));
                    } catch (\Throwable) {
                        return [];
                    }
                },
            );
        });

        $this->singleton(\App\Http\RewriteRuleRegistry::class, function ($app) {
            $db = $app->make(\Cycle\Database\DatabaseInterface::class);
            $prefix = getenv('PW_TABLE_PREFIX') ?: 'pw_';
            $cachePath = $app->storagePath('framework/cache/rewrite-rules.json');
            $registry = new \App\Http\RewriteRuleRegistry($db, $prefix, $cachePath);

            // Try to load from cache first
            if (!$registry->loadFromCache()) {
                try {
                    $registry->loadFromDatabase();
                    $registry->saveToCache();
                } catch (\Throwable) {
                    // Ignore DB errors if not installed yet
                }
            }

            return $registry;
        });

        $this->singleton(\App\Http\ContentTypeDetector::class, function ($app) {
            $db = $app->make(\Cycle\Database\DatabaseInterface::class);
            $prefix = getenv('PW_TABLE_PREFIX') ?: 'pw_';
            return new \App\Http\ContentTypeDetector(
                $db,
                $app->make(\PrestoWorld\Modules\Schema\PostRepository::class),
                $prefix,
            );
        });

        $this->singleton(\App\Http\SmartRouter::class, function ($app) {
            return new \App\Http\SmartRouter(
                $app->make(\App\Http\UrlParser::class),
                $app->make(\App\Http\RewriteRuleRegistry::class),
                $app->make(\App\Http\ContentTypeDetector::class),
                $app->make(\App\Http\TemplateResolver::class),
                $app->make(\App\Contracts\Services\ContentRenderer::class),
                $app->make(\App\Contracts\Http\PageRenderer::class),
                $app->make(\PrestoWorld\Modules\Schema\PostRepository::class),
                $app->make(\Cycle\Database\DatabaseInterface::class),
            );
        });

        // WordPress bridge must register BEFORE DatabaseServiceProvider
        // so it can detect wp-config.php and set DB env vars early.
        $this->register(\PrestoWorld\Bridge\WordPress\BridgeServiceProvider::class);

        $this->register(\Witals\Framework\Auth\AuthServiceProvider::class);
        $this->register(\PrestoWorld\Foundation\Providers\DatabaseServiceProvider::class);
        $this->register(\App\Providers\ConsoleServiceProvider::class);
        $this->register(\App\Providers\PrestoWorldServiceProvider::class);
        $this->register(\PrestoWorld\Plugin\PluginServiceProvider::class);

        // Context system
        $this->singleton(\Witals\Framework\Context\Contracts\BlockManagerInterface::class, \Witals\Framework\Context\BlockManager::class);
        $this->singleton(\Witals\Framework\Context\Contracts\ContextManagerInterface::class, \Witals\Framework\Context\ContextManager::class);
        $this->singleton(\Witals\Framework\Context\Contracts\ContextLoaderInterface::class, function ($app) {
            $themeDir = getenv('PW_THEME_DIR') ?: $app->basePath('content/themes/' . ($app->config('theme.active', 'timeless')));
            return new \Witals\Framework\Context\ContextLoader(
                $app->make(\Witals\Framework\Context\Contracts\BlockManagerInterface::class),
                templateDir: $themeDir . '/templates',
                contextManager: $app->make(\Witals\Framework\Context\Contracts\ContextManagerInterface::class),
            );
        });

        // Persist compiled config cache for long-running environments
        if ($this->isLongRunning() && $this->configRepository !== null) {
            $this->configRepository->persistCache();
        }
    }

    public function boot(): void
    {
        if ($this->booted) {
            return;
        }

        // Register module routes before parent::boot() so they get PRIORITY_MODULE (checked first)
        if ($this->has(\App\Http\Routing\Contracts\RouteRegistryInterface::class)) {
            $registry = $this->make(\App\Http\Routing\Contracts\RouteRegistryInterface::class);

            if ($this->has(\Witals\Framework\Module\ModuleManager::class)) {
                $witalsManager = $this->make(\Witals\Framework\Module\ModuleManager::class);
                // Core modules (e.g. Admin) live directly under framework/presto/
                $witalsManager->addModulePath($this->basePath('framework/presto'));
                $witalsManager->registerModuleRoutes($registry);
            }

            // AuthMiddleware loads the token from the request cookie into the
            // shared AuthContext so admin/auth middlewares can trust getToken().
            $registry->addMiddleware(\Witals\Framework\Auth\Middleware\AuthMiddleware::class);

            // Admin auth only applies to admin routes — lazy-load
            $registry->addMiddlewareFor(
                \App\Http\Middleware\AdminAuthMiddleware::class,
                only: ['/dashboard', '/api/admin', '/site-editor', '/wp-json'],
            );
            // Global middleware runs on all matched routes
            $registry->addMiddleware(\App\Http\Middleware\EnsureInstalledMiddleware::class);
            $registry->addMiddleware(\App\Http\Middleware\CorsMiddleware::class);
            $registry->addMiddleware(\App\Http\Middleware\LocaleMiddleware::class);
        }

        parent::boot();
    }

    public function setConfigPaths(string|array $paths): void
    {
        $paths = is_array($paths) ? $paths : [$paths];
        $this->configRepository = null;
        $repo = $this->resolveConfigRepository();
        $repo->setPaths(
            array_map(fn (string $p) => $this->resolveConfigPath($p), $paths)
        );
    }

    public function addConfigPath(string $path): void
    {
        $repo = $this->resolveConfigRepository();
        $repo->addPath($this->resolveConfigPath($path));
    }

    public function config(string $key, $default = null)
    {
        $repo = $this->resolveConfigRepository();

        $keys = explode('.', $key);
        $file = array_shift($keys);

        $config = $repo->load($file);
        if ($config === null) {
            return $default;
        }

        foreach ($keys as $segment) {
            if (!is_array($config) || !array_key_exists($segment, $config)) {
                return $default;
            }
            $config = $config[$segment];
        }

        return $config;
    }

    public function resolveConfigRepository(): ConfigRepository
    {
        if ($this->configRepository === null) {
            $this->configRepository = new ConfigRepository(
                $this->resolveConfigPath('config')
            );
            $this->configRepository->setCachePath(
                $this->basePath('storage/framework/cache/config.php')
            );
        }

        return $this->configRepository;
    }

    private function resolveConfigPath(string $path): string
    {
        return str_starts_with($path, '/') || str_starts_with($path, '.')
            ? $path
            : $this->basePath($path);
    }

    /**
     * Resolve the active theme directory (env override → config path → default).
     */
    private static function resolveThemePath(self $app): string
    {
        $envPath = getenv('PW_THEME_DIR');
        if (is_string($envPath) && $envPath !== '') {
            return $envPath;
        }

        $themePath = $app->config('theme.path');
        if (is_string($themePath) && $themePath !== '') {
            return $themePath;
        }

        $active = $app->config('theme.active', 'timeless');
        if (!is_string($active) || $active === '') {
            $active = 'timeless';
        }

        return $app->basePath('content/themes/' . $active);
    }

    protected function initializeTranslator(): void
    {
        if ($this->translator === null) {
            $locale = $this->config('app.locale', getenv('APP_LOCALE') ?: 'en');
            $paths = [
                $this->basePath('resources/lang'),
            ];

            $this->translator = new \Witals\Framework\I18n\Translator($locale, $paths);

            $this->instance(\Witals\Framework\Contracts\I18n\TranslatorFactory::class, $this->translator);
            $this->instance('translator', $this->translator);
        }
    }

    /**
     * Initialize logger manager
     */
    protected function initializeLogger(): void
    {
        $this->singleton(LoggerInterface::class, function ($app) {
            // Default to standard buffered logger
            return new StandardLogger(
                path: $app->basePath('storage/logs/presto.log'),
                buffered: true,
                minLevel: getenv('APP_DEBUG') ? 'debug' : 'info'
            );
        });

        $this->alias(LoggerInterface::class, 'log');
    $this->alias(LoggerInterface::class, StandardLogger::class);
    }

    /**
     * Get the destination for PHP error logging.
     * Framework uses this early in the bootstrap process.
     */
    public function getErrorLogPath(): string
    {
        return $this->storagePath('logs/presto.log');
    }
}
