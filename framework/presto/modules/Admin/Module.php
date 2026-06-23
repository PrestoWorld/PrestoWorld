<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\Admin;

use Witals\Framework\Module\Module as WitalsModule;
use Witals\Framework\Contracts\View\Factory as ViewFactory;
use PrestoWorld\Contracts\Admin\Menu\MenuContextRepository as MenuContract;
use PrestoWorld\Modules\Admin\Dashboard\DashboardWidget;

class Module extends WitalsModule
{
    protected bool $skinsRegistered = false;

    public function getName(): string
    {
        return 'PrestoWorld Admin Engine';
    }

    public function register(): void
    {
        $this->registerSkinManager();
        $this->registerMenuRepository();
        $this->registerDashboard();
    }

    public function boot(): void
    {
        $this->registerSkins();
        $this->setActiveSkinFromConfig();
    }

    // ── Skin ─────────────────────────────────────────────────────

    protected function registerSkinManager(): void
    {
        $this->app->singleton(\PrestoWorld\Modules\Admin\SkinManager::class, fn() => new \PrestoWorld\Modules\Admin\SkinManager());
        $this->app->alias(\PrestoWorld\Modules\Admin\SkinManager::class, 'admin.skins');
    }

    protected function registerSkins(): void
    {
        if ($this->skinsRegistered) return;

        $sm = $this->app->make(\PrestoWorld\Modules\Admin\SkinManager::class);
        $this->registerPrestoModernSkin($sm);
        $this->registerPrestoSpaSkin($sm);
        $this->registerWordPressSkin($sm);

        $this->skinsRegistered = true;
    }

    protected function registerPrestoModernSkin(\PrestoWorld\Modules\Admin\SkinManager $manager): void
    {
        $view = $this->app->make(ViewFactory::class);
        $skin = new \PrestoWorld\Modules\Admin\Skins\PrestoModern\PrestoModernSkin($view);
        $manager->registerSkin($skin, $skin::getManifest());
    }

    protected function registerPrestoSpaSkin(\PrestoWorld\Modules\Admin\SkinManager $manager): void
    {
        $assets = $this->app->make(\Witals\Framework\Support\Assets\Contracts\AssetRegistryInterface::class);
        $skin = new \PrestoWorld\Modules\Admin\Skins\PrestoSpa\PrestoSpaSkin($assets);
        $manager->registerSkin($skin, $skin::getManifest());
    }

    protected function registerWordPressSkin(\PrestoWorld\Modules\Admin\SkinManager $manager): void
    {
        $view = $this->app->make(ViewFactory::class);
        $skin = new \PrestoWorld\Bridge\WordPress\Admin\Skins\WordPressSkin($view);
        $manager->registerSkin($skin, \PrestoWorld\Bridge\WordPress\Admin\Skins\WordPressSkin::getManifest());
    }

    protected function setActiveSkinFromConfig(): void
    {
        $skinName = $this->app->config('admin.skin', 'presto-spa');
        $skinManager = $this->app->make(\PrestoWorld\Modules\Admin\SkinManager::class);

        if ($skinManager->hasSkin($skinName)) {
            $skinManager->setActiveSkin($skinName);
        }
    }

    // ── Menu ─────────────────────────────────────────────────────

    protected function registerMenuRepository(): void
    {
        $this->app->singleton(MenuContract::class, function () {
            $menu = new \PrestoWorld\Modules\Admin\Menu\MenuContextRepository();

            $this->registerDefaultMenuItems($menu);

            return $menu;
        });
        $this->app->alias(MenuContract::class, 'admin.menu');
    }

    protected function registerDefaultMenuItems(MenuContract $menu): void
    {
        $m = 1; // Dashboard
        $menu->registerGroup('dashboard-group', 'Dashboard', icon: 'LayoutDashboard', priority: $m++);
        $menu->registerItem('dashboard-group', 'Dashboard', '#/dashboard', icon: 'LayoutDashboard', priority: 10, id: 'dashboard', screenId: 'dashboard');

        $m++; // Posts
        $menu->registerGroup('posts-group', 'Posts', icon: 'FileText', priority: $m++);
        $menu->registerItem('posts-group', 'All Posts', '#/posts', icon: 'FileText', priority: 10, id: 'posts', screenId: 'posts');
        $menu->registerItem('posts-group', 'Add New', '#/post-new', icon: 'Plus', priority: 20, id: 'post-new', screenId: 'post-new');
        $menu->registerItem('posts-group', 'Categories', '#/edit-tags?taxonomy=category', icon: 'BookOpen', priority: 30, id: 'post-categories', screenId: 'edit-tags');
        $menu->registerItem('posts-group', 'Tags', '#/edit-tags?taxonomy=post_tag', icon: 'Tags', priority: 40, id: 'post-tags', screenId: 'edit-tags');

        $m++; // Media
        $menu->registerGroup('media-group', 'Media', icon: 'Image', priority: $m++);
        $menu->registerItem('media-group', 'Library', '#/upload', icon: 'Image', priority: 10, id: 'media', screenId: 'upload');
        $menu->registerItem('media-group', 'Add New', '#/media-new', icon: 'Plus', priority: 20, id: 'media-new', screenId: 'media-new');

        $m++; // Pages
        $menu->registerGroup('pages-group', 'Pages', icon: 'File', priority: $m++);
        $menu->registerItem('pages-group', 'All Pages', '#/edit-pages', icon: 'File', priority: 10, id: 'pages', screenId: 'edit-pages');
        $menu->registerItem('pages-group', 'Add New', '#/post-new?post_type=page', icon: 'Plus', priority: 20, id: 'page-new', screenId: 'page-new');

        $m++; // Comments
        $menu->registerGroup('comments-group', 'Comments', icon: 'MessageSquare', priority: $m++);
        $menu->registerItem('comments-group', 'All Comments', '#/edit-comments', icon: 'MessageSquare', priority: 10, id: 'comments', screenId: 'edit-comments');

        $m++; // Appearance
        $menu->registerGroup('appearance-group', 'Appearance', icon: 'Palette', priority: $m++);
        $menu->registerItem('appearance-group', 'Themes', '#/themes', icon: 'Palette', priority: 10, id: 'themes', screenId: 'themes');
        $menu->registerItem('appearance-group', 'Customize', '#/customize', icon: 'Wrench', priority: 20, id: 'customize', screenId: 'customize');
        $menu->registerItem('appearance-group', 'Widgets', '#/widgets', icon: 'Blocks', priority: 30, id: 'widgets', screenId: 'widgets');
        $menu->registerItem('appearance-group', 'Menus', '#/nav-menus', icon: 'Menu', priority: 40, id: 'menus', screenId: 'nav-menus');
        $menu->registerItem('appearance-group', 'Theme Editor', '#/theme-editor', icon: 'Code', priority: 50, id: 'theme-editor', screenId: 'theme-editor');

        $m++; // Plugins
        $menu->registerGroup('plugins-group', 'Plugins', icon: 'Puzzle', priority: $m++);
        $menu->registerItem('plugins-group', 'Installed Plugins', '#/plugins', icon: 'Puzzle', priority: 10, id: 'plugins', screenId: 'plugins');
        $menu->registerItem('plugins-group', 'Add New', '#/plugin-install', icon: 'Plus', priority: 20, id: 'plugin-install', screenId: 'plugin-install');
        $menu->registerItem('plugins-group', 'Plugin Editor', '#/plugin-editor', icon: 'Code', priority: 30, id: 'plugin-editor', screenId: 'plugin-editor');

        $m++; // Users
        $menu->registerGroup('users-group', 'Users', icon: 'User', priority: $m++);
        $menu->registerItem('users-group', 'All Users', '#/users', icon: 'User', priority: 10, id: 'users', screenId: 'users');
        $menu->registerItem('users-group', 'Add New', '#/user-new', icon: 'Plus', priority: 20, id: 'user-new', screenId: 'user-new');
        $menu->registerItem('users-group', 'Profile', '#/profile', icon: 'UserCheck', priority: 30, id: 'profile', screenId: 'profile');

        $m++; // Tools
        $menu->registerGroup('tools-group', 'Tools', icon: 'Wrench', priority: $m++);
        $menu->registerItem('tools-group', 'Available Tools', '#/tools', icon: 'Wrench', priority: 10, id: 'tools', screenId: 'tools');
        $menu->registerItem('tools-group', 'Import', '#/import', icon: 'Download', priority: 20, id: 'import', screenId: 'import');
        $menu->registerItem('tools-group', 'Export', '#/export', icon: 'Upload', priority: 30, id: 'export', screenId: 'export');
        $menu->registerItem('tools-group', 'Site Health', '#/site-health', icon: 'Activity', priority: 40, id: 'site-health', screenId: 'site-health');

        $m++; // Settings
        $menu->registerGroup('settings-group', 'Settings', icon: 'Settings', priority: $m++);
        $menu->registerItem('settings-group', 'General', '#/settings', icon: 'Settings', priority: 10, id: 'settings', screenId: 'settings');
        $menu->registerItem('settings-group', 'Writing', '#/options-writing', icon: 'Edit', priority: 20, id: 'options-writing', screenId: 'options-writing');
        $menu->registerItem('settings-group', 'Reading', '#/options-reading', icon: 'BookOpen', priority: 30, id: 'options-reading', screenId: 'options-reading');
        $menu->registerItem('settings-group', 'Discussion', '#/options-discussion', icon: 'MessageSquare', priority: 40, id: 'options-discussion', screenId: 'options-discussion');
        $menu->registerItem('settings-group', 'Media', '#/options-media', icon: 'Image', priority: 50, id: 'options-media', screenId: 'options-media');
        $menu->registerItem('settings-group', 'Permalinks', '#/options-permalink', icon: 'Link', priority: 60, id: 'options-permalink', screenId: 'options-permalink');
        $menu->registerItem('settings-group', 'Privacy', '#/options-privacy', icon: 'Shield', priority: 70, id: 'options-privacy', screenId: 'options-privacy');
    }

    // ── Dashboard & Widgets ──────────────────────────────────────

    protected function registerDashboard(): void
    {
        $this->app->singleton(
            \PrestoWorld\Contracts\Admin\Dashboard\DashboardWidgetRepository::class,
            fn() => new \PrestoWorld\Modules\Admin\Dashboard\DashboardWidgetRepository(),
        );

        $this->registerDashboardWidgets();
    }

    protected function registerDashboardWidgets(): void
    {
        $repo = $this->app->make(\PrestoWorld\Contracts\Admin\Dashboard\DashboardWidgetRepository::class);
        $column = fn(int $idx) => $idx <= 2 ? 1 : 2;

        $widgets = [
            ['at-a-glance', 'At a Glance', '<div class="pw-dashboard-stats"><div class="pw-stat"><span class="pw-stat__value">—</span><span class="pw-stat__label">Posts</span></div><div class="pw-stat"><span class="pw-stat__value">—</span><span class="pw-stat__label">Users</span></div></div>'],
            ['quick-draft', 'Quick Draft', '<form class="pw-quick-draft"><textarea class="pw-quick-draft__input" placeholder="What\'s on your mind?" rows="3"></textarea><button class="pw-quick-draft__submit" type="submit">Save Draft</button></form>'],
            ['activity', 'Activity', '<div class="pw-activity"><p class="pw-activity__empty">No recent activity.</p></div>'],
            ['events-news', 'Events & News', '<div class="pw-news"><p class="pw-news__empty">No recent news.</p></div>'],
        ];

        foreach ($widgets as $i => [$id, $title, $content]) {
            $repo->registerWidget(new DashboardWidget(
                id: $id,
                title: $title,
                content: $content,
                priority: $i + 1,
                column: $column($i + 1),
            ));
        }
    }
}
