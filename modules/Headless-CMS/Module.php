<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\HeadlessCMS;

use Witals\Framework\Module\ModuleInterface;
use Witals\Framework\Module\ModuleProvider;
use Witals\Framework\Contracts\Container\ContainerInterface;

class Module extends ModuleProvider implements ModuleInterface
{
    public function getName(): string
    {
        return 'headless-cms';
    }

    public function getVersion(): string
    {
        return '1.0.0';
    }

    public function getDependencies(): array
    {
        return ['schema', 'gutenberg'];
    }

    public function register(ContainerInterface $container): void
    {
        // Register API controllers
        $container->singleton(\PrestoWorld\Modules\HeadlessCMS\Controller\PostController::class);
        $container->singleton(\PrestoWorld\Modules\HeadlessCMS\Controller\TermController::class);
        $container->singleton(\PrestoWorld\Modules\HeadlessCMS\Controller\MediaController::class);
        $container->singleton(\PrestoWorld\Modules\HeadlessCMS\Controller\MenuController::class);
        $container->singleton(\PrestoWorld\Modules\HeadlessCMS\Controller\SettingController::class);
        $container->singleton(\PrestoWorld\Modules\HeadlessCMS\Controller\UserController::class);
        $container->singleton(\PrestoWorld\Modules\HeadlessCMS\Controller\SearchController::class);

        // Register serializers
        $container->singleton(\PrestoWorld\Modules\HeadlessCMS\Serializer\PostSerializer::class);
        $container->singleton(\PrestoWorld\Modules\HeadlessCMS\Serializer\TermSerializer::class);
        $container->singleton(\PrestoWorld\Modules\HeadlessCMS\Serializer\MediaSerializer::class);
        $container->singleton(\PrestoWorld\Modules\HeadlessCMS\Serializer\MenuSerializer::class);
        $container->singleton(\PrestoWorld\Modules\HeadlessCMS\Serializer\SettingSerializer::class);
        $container->singleton(\PrestoWorld\Modules\HeadlessCMS\Serializer\UserSerializer::class);

        // Register transformers
        $container->singleton(\PrestoWorld\Modules\HeadlessCMS\Transformer\PostTransformer::class);
        $container->singleton(\PrestoWorld\Modules\HeadlessCMS\Transformer\TermTransformer::class);
        $container->singleton(\PrestoWorld\Modules\HeadlessCMS\Transformer\MediaTransformer::class);
    }

    public function boot(ContainerInterface $container): void
    {
        // Register API routes
        $this->registerRoutes($container);
    }

    protected function registerRoutes(ContainerInterface $container): void
    {
        $router = $container->get(\Witals\Framework\Http\Routing\Contracts\RouterInterface::class);
        $prefix = '/api/v1';

        // Posts
        $router->get($prefix . '/posts', [\PrestoWorld\Modules\HeadlessCMS\Controller\PostController::class, 'index']);
        $router->get($prefix . '/posts/{id}', [\PrestoWorld\Modules\HeadlessCMS\Controller\PostController::class, 'show']);
        $router->get($prefix . '/posts/by-slug', [\PrestoWorld\Modules\HeadlessCMS\Controller\PostController::class, 'showBySlug']);
        $router->get($prefix . '/posts/{id}/related', [\PrestoWorld\Modules\HeadlessCMS\Controller\PostController::class, 'related']);

        // Terms (Categories, Tags, Custom Taxonomies)
        $router->get($prefix . '/terms', [\PrestoWorld\Modules\HeadlessCMS\Controller\TermController::class, 'index']);
        $router->get($prefix . '/terms/{id}', [\PrestoWorld\Modules\HeadlessCMS\Controller\TermController::class, 'show']);
        $router->get($prefix . '/terms/by-slug', [\PrestoWorld\Modules\HeadlessCMS\Controller\TermController::class, 'showBySlug']);

        // Media
        $router->get($prefix . '/media', [\PrestoWorld\Modules\HeadlessCMS\Controller\MediaController::class, 'index']);
        $router->get($prefix . '/media/{id}', [\PrestoWorld\Modules\HeadlessCMS\Controller\MediaController::class, 'show']);

        // Menus
        $router->get($prefix . '/menus', [\PrestoWorld\Modules\HeadlessCMS\Controller\MenuController::class, 'index']);
        $router->get($prefix . '/menus/{slug}', [\PrestoWorld\Modules\HeadlessCMS\Controller\MenuController::class, 'show']);

        // Settings
        $router->get($prefix . '/settings', [\PrestoWorld\Modules\HeadlessCMS\Controller\SettingController::class, 'show']);

        // Users
        $router->get($prefix . '/users', [\PrestoWorld\Modules\HeadlessCMS\Controller\UserController::class, 'index']);
        $router->get($prefix . '/users/{id}', [\PrestoWorld\Modules\HeadlessCMS\Controller\UserController::class, 'show']);

        // Search
        $router->get($prefix . '/search', [\PrestoWorld\Modules\HeadlessCMS\Controller\SearchController::class, 'search']);
    }
}