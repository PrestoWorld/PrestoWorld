<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\ContextBuilder;

use Witals\Framework\Module\Module as WitalsModule;
use PrestoWorld\Gutenberg\Gutenberg;
use PrestoWorld\Gutenberg\Kernel\Kernel;
use PrestoWorld\Gutenberg\Theme\ThemeBooter;

class Module extends WitalsModule
{
    public function __construct(
        protected \Witals\Framework\Application $app,
        protected string $path = '',
        protected array $metadata = [],
    ) {
        if ($path === '') {
            $path = __DIR__;
        }
        if ($metadata === []) {
            $metadata = ['name' => 'context-builder'];
        }
        parent::__construct($app, $path, $metadata);
    }

    public function getName(): string
    {
        return 'PrestoWorld Context Builder';
    }

    public function register(): void
    {
        $this->app->singleton(BlockRegistry::class, function () {
            return new BlockRegistry();
        });

        $this->app->singleton(ContextRegistry::class, function () {
            return new ContextRegistry();
        });

        // Cache bridge: host owns policy (salt/gen/TTL/stats) — gutenberg-core
        // chỉ đi qua interface. Đăng ký trước khi kernel/parser chạy.
        \PrestoWorld\Core\Cache\GutenbergBridge::register();
        \PrestoWorld\Gutenberg\Core\Blocks\TemplatePartHandler::$cacheLookup = static function (string $slug, array $attributes): ?string {
            return \PrestoWorld\Core\Cache\FragmentCache::lookup($slug, $attributes);
        };
        \PrestoWorld\Gutenberg\Core\Blocks\TemplatePartHandler::$cacheStore = static function (string $slug, array $attributes, string $html): void {
            \PrestoWorld\Core\Cache\FragmentCache::store($slug, $attributes, $html);
        };

        // Gutenberg engine kernel (vendor/prestoworld/gutenberg-core).
        $this->app->singleton(Kernel::class, function ($app) {
            return Gutenberg::boot($this->resolveThemePath(), $this->renderServices($app));
        });

        $this->app->singleton('Presto\\Context\\Loader', function ($app) {
            $kernel = $app->make(Kernel::class);

            $loader = new \Presto\Context\Loader(
                $app->make(BlockRegistry::class),
                $app->basePath('storage/contexts'),
                $this->resolveThemePath(),
            );

            $loader->setBlockRenderer($kernel->renderer());
            $loader->setBlockParser($kernel->parser());

            return $loader;
        });

        $this->app->singleton(ContextBuilder::class, function ($app) {
            return new ContextBuilder(
                $app->make(ContextRegistry::class),
                $app->make('Presto\\Context\\Loader'),
                $app->make(BlockRegistry::class),
            );
        });
    }

    public function boot(): void
    {
        // Theme setup (WP lifecycle): boot the active theme's functions.php
        // so register_block_style/pattern/bindings land in the kernel.
        ThemeBooter::boot($this->resolveThemePath());

        // Fire init now that functions.php registrations are in place.
        ThemeBooter::dispatch('init');

        $this->registerCoreBlocks();
        $this->registerRestRoutes();
    }

    /**
     * Application services exposed to block render callbacks.
     *
     * @return array<string, mixed>
     */
    protected function renderServices($app): array
    {
        $services = [
            'theme_dir' => $this->resolveThemePath(),
            'site_url' => function_exists('home_url') ? (string) \home_url('/') : '/',
            'pagination_url' => $_SERVER['REQUEST_URI'] ?? '/',
        ];

        if (class_exists(\PrestoWorld\Modules\Schema\PostRepository::class) && $app->has(\PrestoWorld\Modules\Schema\PostRepository::class)) {
            $repository = $app->make(\PrestoWorld\Modules\Schema\PostRepository::class);
            $services['post_repository'] = $repository;
            $services['post_resolver'] = static function (int $id) use ($repository): ?array {
                $rows = $repository->find(['id' => $id, 'per_page' => 1]);

                return is_array($rows[0] ?? null) ? $rows[0] : null;
            };
        }

        return $services;
    }

    protected function registerRestRoutes(): void
    {
        if (!function_exists('register_rest_route')) {
            return;
        }

        $controller = new Rest\ContextBuilderController(
            $this->makeIfAvailable(ContextBuilder::class),
        );
        $controller->register_routes();

        $gutenbergController = new Rest\GutenbergRestController();
        $gutenbergController->register_routes();
    }

    protected function registerCoreBlocks(): void
    {
        $registry = $this->makeIfAvailable(BlockRegistry::class);

        if (!$registry instanceof BlockRegistry) {
            return;
        }

        $registry->register(new Block\HeadingBlock());
        $registry->register(new Block\ParagraphBlock());
        $registry->register(new Block\ImageBlock());
        $registry->register(new Block\QueryLoopBlock());

        // Register default context types
        $contextRegistry = $this->makeIfAvailable(ContextRegistry::class);

        if ($contextRegistry instanceof ContextRegistry) {
            $contextRegistry->register(new ContextType('post', 'Posts', [
                'render_mode' => 'ssr',
                'regions' => ['header', 'content', 'sidebar', 'footer'],
            ]));
            $contextRegistry->register(new ContextType('page', 'Pages', [
                'render_mode' => 'ssr',
                'regions' => ['header', 'content', 'sidebar', 'footer'],
            ]));
            $contextRegistry->register(new ContextType('taxonomy', 'Taxonomy', [
                'render_mode' => 'ssr',
                'regions' => ['header', 'content', 'sidebar', 'footer'],
            ]));
        }
    }

    /**
     * Resolve the active theme's path.
     */
    protected function resolveThemePath(): string
    {
        $themePath = getenv('PW_THEME_DIR');
        if (!is_string($themePath) || $themePath === '') {
            $themePath = $this->app->basePath('content/themes/' . $this->app->config('theme.active', 'timeless'));
        }

        return $themePath;
    }

    private function makeIfAvailable(string $class): mixed
    {
        try {
            return $this->app->has($class) ? $this->app->make($class) : null;
        } catch (\Throwable) {
            return null;
        }
    }
}
