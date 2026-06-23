<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use Witals\Framework\Http\Request;
use Witals\Framework\Http\Response;
use PrestoWorld\Modules\Admin\SkinManager;
use PrestoWorld\Contracts\Admin\Menu\MenuContextRepository;
use PrestoWorld\Contracts\Admin\Dashboard\DashboardWidgetRepository;
use PrestoWorld\Modules\Admin\ScreenOptions\ScreenOption;
use PrestoWorld\Modules\Admin\ScreenOptions\ScreenOptionsContext;
use PrestoWorld\Modules\Admin\AdminBar\AdminBarItem;
use PrestoWorld\Modules\Admin\AdminBar\AdminBarContext;

class SpaController
{
    /** Widget-to-component mapping */
    protected const WIDGET_COMPONENTS = [
        'at-a-glance'  => 'StatCards',
        'quick-draft'  => 'QuickDraft',
        'activity'     => 'ActivityLog',
        'events-news'  => 'EventsNews',
    ];

    public function __construct(
        protected SkinManager $skins,
        protected MenuContextRepository $menu,
        protected DashboardWidgetRepository $widgets,
    ) {}

    protected const WP_SCREEN_MAP = [
        // Dashboard
        'index.php'           => 'dashboard',
        'admin.php'           => null, // determined by ?page= param

        // Posts
        'edit.php'            => 'posts',
        'post-new.php'        => 'post-new',
        'post.php'            => 'post',

        // Media
        'upload.php'          => 'upload',
        'media-new.php'       => 'media-new',

        // Pages
        'edit-pages.php'     => 'edit-pages',

        // Comments
        'edit-comments.php'  => 'edit-comments',

        // Appearance
        'themes.php'          => 'themes',
        'customize.php'       => 'customize',
        'widgets.php'         => 'widgets',
        'nav-menus.php'       => 'nav-menus',
        'theme-editor.php'    => 'theme-editor',

        // Plugins
        'plugins.php'         => 'plugins',
        'plugin-install.php'  => 'plugin-install',
        'plugin-editor.php'   => 'plugin-editor',

        // Users
        'users.php'           => 'users',
        'user-new.php'        => 'user-new',
        'user-edit.php'       => 'user-edit',
        'profile.php'         => 'profile',

        // Tools
        'tools.php'           => 'tools',
        'import.php'          => 'import',
        'export.php'          => 'export',
        'site-health.php'     => 'site-health',
        'site-health-info.php'=> 'site-health-info',

        // Settings
        'options-general.php' => 'settings',
        'options-writing.php' => 'options-writing',
        'options-reading.php' => 'options-reading',
        'options-discussion.php' => 'options-discussion',
        'options-media.php'   => 'options-media',
        'options-permalink.php' => 'options-permalink',
        'options-privacy.php' => 'options-privacy',

        // Updates
        'update-core.php'     => 'update-core',
    ];

    public function __invoke(Request $request): Response
    {
        $path = $request->path();
        $skin = $this->resolveSkin($path, $request);
        $screenId = $this->resolveScreenId($request);

        $menuSections = $this->buildMenuSections();
        $screens = $this->buildScreens($menuSections);

        $screenTitle = 'Dashboard';
        foreach ($screens as $s) {
            if (($s['id'] ?? '') === $screenId) {
                $screenTitle = $s['title'] ?? 'Dashboard';
                break;
            }
        }

        $initialState = [
            'user' => $this->getUserState(),
            'screens' => $screens,
            'menuSections' => $menuSections,
            'widgets' => $this->buildWidgets(),
            'screenOptions' => $this->buildScreenOptions(),
            'adminBar' => $this->buildAdminBar()->toArray(),
            'page' => [
                'path' => $request->path(),
                'title' => $screenTitle,
                'screenId' => $screenId,
            ],
        ];

        $html = $skin->renderLayout('', [
            'title' => $screenTitle,
            'initialState' => $initialState,
        ]);

        return Response::html($html);
    }

    protected function resolveSkin(string $path, Request $request): mixed
    {
        // /wp-admin/* routes always use the WordPress SSR skin
        if (str_starts_with($path, '/wp-admin')) {
            if ($this->skins->hasSkin('wordpress-classic')) {
                return $this->skins->getSkin('wordpress-classic');
            }
        }

        // ?skin= query param override
        $querySkin = $request->query('skin');
        if ($querySkin !== null && $this->skins->hasSkin($querySkin)) {
            return $this->skins->getSkin($querySkin);
        }

        return $this->skins->getActiveSkin();
    }

    protected function resolveScreenId(Request $request): string
    {
        $path = $request->path();
        $scriptName = basename($path);

        // Direct ?screen= param override
        $queryScreen = $request->query('screen');
        if ($queryScreen !== null) {
            return $queryScreen;
        }

        // Map WordPress script names to screen IDs
        if (isset(self::WP_SCREEN_MAP[$scriptName])) {
            $mapped = self::WP_SCREEN_MAP[$scriptName];
            if ($mapped !== null) {
                return $mapped;
            }
            // admin.php — get from ?page= param
            return $request->query('page', 'dashboard');
        }

        // /wp-admin/ root → dashboard
        if (in_array($path, ['/wp-admin', '/wp-admin/'], true)) {
            return 'dashboard';
        }

        return 'dashboard';
    }

    public function adminAjax(Request $request): Response
    {
        $action = $request->query('action', $request->post('action', ''));
        return Response::json([
            'success' => false,
            'data' => [],
            'error' => "WP Ajax action '{$action}' is not implemented in PrestoWorld.",
        ]);
    }

    public function menu(Request $request): Response
    {
        return Response::json([
            'menuSections' => $this->buildMenuSections(),
        ]);
    }

    public function dashboardWidgets(Request $request): Response
    {
        return Response::json($this->buildWidgets());
    }

    public function menuTree(): Response
    {
        return Response::json($this->menu->getTreeAsArray());
    }

    /** Build screens list from registered menu items */
    protected function buildScreens(array $menuSections): array
    {
        $screens = [];
        $seen = [];

        foreach ($menuSections as $section) {
            foreach ($section['items'] ?? [] as $item) {
                $screenId = $item['screenId'] ?? null;
                if ($screenId !== null && !isset($seen[$screenId])) {
                    $seen[$screenId] = true;
                    $screens[] = [
                        'id' => $screenId,
                        'title' => $item['label'],
                        'icon' => $item['icon'] ?? 'Circle',
                        'position' => $item['priority'] ?? count($screens) * 10,
                    ];
                }
            }
        }

        return $screens;
    }

    /** Build sidebar sections from the registered menu tree */
    protected function buildMenuSections(): array
    {
        $menuTree = $this->menu->getTreeAsArray();
        $sections = [];

        foreach ($menuTree as $i => $group) {
            $children = $group['children'] ?? [];

            // Use the first child as the primary screenId/icon for the group
            $firstChild = $children[0] ?? [];
            $screenId = $firstChild['screenId'] ?? $firstChild['id'] ?? '';
            $icon = $firstChild['icon'] ?? $group['icon'] ?? 'Circle';

            $sections[] = [
                'id' => $group['id'] ?? 'section-' . $i,
                'title' => $group['label'] ?? '',
                'priority' => $group['priority'] ?? 10,
                'icon' => $icon,
                'screenId' => $screenId,
                'items' => array_map(fn(array $child) => [
                    'id' => $child['id'] ?? $child['screenId'] ?? '',
                    'screenId' => $child['screenId'] ?? $child['id'] ?? '',
                    'label' => $child['label'] ?? '',
                    'icon' => $child['icon'] ?? $icon,
                    'url' => $child['url'] ?? '',
                    'priority' => $child['priority'] ?? 10,
                ], $children),
            ];
        }

        return $sections;
    }

    /** Build widget definitions with SPA component mapping */
    protected function buildWidgets(): array
    {
        $definitions = [];

        foreach ($this->widgets->getWidgets() as $widget) {
            $id = $widget->getId();
            $component = self::WIDGET_COMPONENTS[$id] ?? '';

            $grid = match ($widget->getColumn()) {
                1 => 'half',
                2 => 'half',
                default => 'full',
            };

            $definitions[] = [
                'id' => $widget->getId(),
                'title' => $widget->getTitle(),
                'component' => $component,
                'grid' => $grid,
                'priority' => $widget->getPriority(),
                'visible' => $widget->isVisible(),
                'props' => [
                    'content' => $widget->getContent(),
                    'column' => $widget->getColumn(),
                ],
            ];
        }

        return $definitions;
    }

    protected function buildScreenOptions(): array
    {
        $postsOptions = new ScreenOptionsContext('posts', 'Posts Settings');
        $postsOptions->addOption(new ScreenOption(
            id: 'posts_per_page',
            label: 'Posts per page',
            type: 'number',
            default: 20,
        ));
        $postsOptions->addOption(new ScreenOption(
            id: 'show_comments',
            label: 'Show comment count',
            type: 'checkbox',
            default: true,
        ));

        $pluginsOptions = new ScreenOptionsContext('plugins', 'Plugins Settings');
        $pluginsOptions->addOption(new ScreenOption(
            id: 'show_deprecated',
            label: 'Show deprecated plugins',
            type: 'checkbox',
            default: false,
        ));

        return [
            $postsOptions->toArray(),
            $pluginsOptions->toArray(),
        ];
    }

    protected function buildAdminBar(): AdminBarContext
    {
        $bar = new AdminBarContext();

        $bar->addItem(new AdminBarItem(
            id: 'visit-site',
            label: 'Visit Site',
            icon: 'Globe',
            href: '/',
            type: 'link',
        ));

        $notification = new AdminBarItem(
            id: 'notifications',
            label: 'Notifications',
            icon: 'Bell',
            type: 'notification',
            badge: 3,
        );

        $bar->addItem(new AdminBarItem(
            id: 'new-post',
            label: 'New Post',
            icon: 'Plus',
            type: 'button',
        ));

        $bar->addItem($notification);

        return $bar;
    }

    protected function getUserState(): array
    {
        return [
            'name' => 'Administrator',
            'role' => 'admin',
            'avatar' => null,
        ];
    }
}
