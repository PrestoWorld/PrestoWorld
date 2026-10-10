<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\ContextBuilder;

use Witals\Framework\Module\Module as WitalsModule;
use PrestoWorld\Modules\Gutenberg\Theme\ThemeBooter;

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
        $this->app->singleton(BlockRegistry::class, function ($app) {
            return new BlockRegistry();
        });

        $this->app->singleton(ContextRegistry::class, function ($app) {
            return new ContextRegistry();
        });

        $this->app->singleton(ContextLoader::class, function ($app) {
            $themePath = getenv('PW_THEME_DIR');
            if (!is_string($themePath) || $themePath === '') {
                $themePath = $app->basePath('content/themes/' . $app->config('theme.active', 'jankx'));
            }

            $loader = new ContextLoader(
                $app->make(BlockRegistry::class),
                $app->basePath('storage/contexts'),
                $themePath
            );

            // Inject BlockRenderer if available
            if ($app->has(\PrestoWorld\Modules\Gutenberg\Renderer\BlockRenderer::class)) {
                $loader->setBlockRenderer(
                    $app->make(\PrestoWorld\Modules\Gutenberg\Renderer\BlockRenderer::class)
                );
                $loader->setBlockParser(
                    $app->make(\PrestoWorld\Modules\Gutenberg\Parser\BlockParser::class)
                );
            }

            return $loader;
        });

        $this->app->singleton(ContextBuilder::class, function ($app) {
            return new ContextBuilder(
                $app->make(ContextRegistry::class),
                $app->make(ContextLoader::class),
                $app->make(BlockRegistry::class),
            );
        });
    }

    public function boot(): void
    {
        // Theme setup (WP lifecycle): boot the active theme's functions.php.
        // The theme decides how to load per runtime (WordPress vs PrestoWorld)
        // and handles its own context blocks on the legacy registry; the
        // framework never hardcodes a theme's block names.
        ThemeBooter::provideBlockRegistry($this->app->make(BlockRegistry::class));
        ThemeBooter::boot($this->resolveThemePath());

        $this->registerCoreBlocks();
        $this->registerRestRoutes();
    }

    protected function registerRestRoutes(): void
    {
        if (!function_exists('register_rest_route')) {
            return;
        }

        $controller = new Rest\ContextBuilderController(
            $this->app->make(ContextBuilder::class),
        );
        $controller->register_routes();

        $gutenbergController = new Rest\GutenbergRestController();
        $gutenbergController->register_routes();
    }

    protected function registerCoreBlocks(): void
    {
        $registry = $this->app->make(BlockRegistry::class);

        $registry->register(new Block\HeadingBlock());
        $registry->register(new Block\ParagraphBlock());
        $registry->register(new Block\ImageBlock());
        $registry->register(new Block\QueryLoopBlock());

        // Register default context types
        $contextRegistry = $this->app->make(ContextRegistry::class);
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

    /**
     * Resolve the active theme's path.
     */
    protected function resolveThemePath(): string
    {
        $themePath = getenv('PW_THEME_DIR');
        if (!is_string($themePath) || $themePath === '') {
            $themePath = $this->app->basePath('content/themes/' . $this->app->config('theme.active', 'jankx'));
        }

        return $themePath;
    }
}
