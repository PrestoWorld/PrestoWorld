<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use Witals\Framework\Http\Request;
use Witals\Framework\Http\Response;
use PrestoWorld\Modules\Admin\SkinManager;
use PrestoWorld\Contracts\Admin\Menu\MenuContextRepository;
use PrestoWorld\Contracts\Admin\Dashboard\DashboardWidgetRepository;
use PrestoWorld\Contracts\Admin\Menu\MenuItem as MenuItemContract;
use PrestoWorld\Modules\Admin\ScreenOptions\ScreenOption;
use PrestoWorld\Modules\Admin\ScreenOptions\ScreenOptionsContext;
use PrestoWorld\Modules\Admin\AdminBar\AdminBarItem;
use PrestoWorld\Modules\Admin\AdminBar\AdminBarContext;
use PrestoWorld\Modules\Admin\Menu\MenuSection;

class SpaController
{
    /** Known screen IDs */
    protected const SCREENS = [
        ['id' => 'dashboard', 'title' => 'Dashboard', 'icon' => 'LayoutDashboard', 'position' => 0],
        ['id' => 'posts',     'title' => 'Posts',     'icon' => 'FileText',       'position' => 10],
        ['id' => 'plugins',   'title' => 'Plugins',   'icon' => 'Blocks',         'position' => 20],
        ['id' => 'settings',  'title' => 'Settings',  'icon' => 'Settings',       'position' => 30],
    ];

    /** Known menu sections sidebar groups */
    protected const MENU_SECTIONS = [
        [
            'id' => 'management',
            'title' => 'Management',
            'priority' => 10,
            'items' => [
                ['screenId' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'LayoutDashboard'],
                ['screenId' => 'posts',     'label' => 'Posts',     'icon' => 'FileText'],
                ['screenId' => 'plugins',   'label' => 'Plugins',   'icon' => 'Blocks'],
            ],
        ],
        [
            'id' => 'configuration',
            'title' => 'Configuration',
            'priority' => 20,
            'items' => [
                ['screenId' => 'settings', 'label' => 'Settings', 'icon' => 'Settings'],
            ],
        ],
    ];

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

    public function __invoke(Request $request): Response
    {
        $skin = $this->skins->getActiveSkin();

        $initialState = [
            'user' => $this->getUserState(),
            'screens' => self::SCREENS,
            'menuSections' => $this->buildMenuSections(),
            'widgets' => $this->buildWidgets(),
            'screenOptions' => $this->buildScreenOptions(),
            'adminBar' => $this->buildAdminBar()->toArray(),
            'page' => [
                'path' => $request->path(),
                'title' => 'Dashboard',
                'screenId' => 'dashboard',
            ],
        ];

        $html = $skin->renderLayout('', [
            'title' => 'Admin',
            'initialState' => $initialState,
        ]);

        return Response::html($html);
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

    public function providers(): Response
    {
        return Response::json([
            'providers' => array_map(
                fn($p) => [
                    'identifier' => $p->getIdentifier(),
                    'priority' => $p->getPriority(),
                ],
                $this->menu->getProviders(),
            ),
        ]);
    }

    /** Build sidebar sections from the registered menu tree */
    protected function buildMenuSections(): array
    {
        $menuTree = $this->menu->getTreeAsArray();

        return array_map(function (array $section, int $idx) {
            $sectionDef = self::MENU_SECTIONS[$idx] ?? [];

            return [
                'id' => $sectionDef['id'] ?? 'section-' . $idx,
                'title' => $sectionDef['title'] ?? $section['label'] ?? '',
                'priority' => $sectionDef['priority'] ?? 10,
                'items' => $section['children'] ?? [$section],
            ];
        }, $menuTree, array_keys($menuTree));
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
