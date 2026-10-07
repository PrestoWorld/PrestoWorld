<?php

declare(strict_types=1);

namespace PrestoWorld\Plugins\SEO;

use PrestoWorld\Plugin\Plugin as BasePlugin;
use PrestoWorld\Plugin\HookDispatcher;

class Plugin extends BasePlugin
{
    public function register(): void
    {
        parent::register();

        $hooks = $this->app->make(HookDispatcher::class);

        $hooks->addAction('admin.sidebar.menu', [$this, 'registerAdminMenu']);
        $hooks->addFilter('content.head.meta', [$this, 'renderMetaTags']);
        $hooks->addAction('seo.sitemap.generate', [$this, 'generateSitemap']);
    }

    public function boot(): void
    {
        parent::boot();
    }

    public function activate(): void
    {
        $this->logger?->info('SEO Manager plugin activated');
    }

    public function deactivate(): void
    {
        $this->logger?->info('SEO Manager plugin deactivated');
    }

    public function registerAdminMenu(): void
    {
    }

    public function renderMetaTags(string $content): string
    {
        return $content;
    }

    public function generateSitemap(): void
    {
    }
}
