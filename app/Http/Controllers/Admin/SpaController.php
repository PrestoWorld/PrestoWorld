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

    public function __invoke(Request $request): Response
    {
        $skin = $this->skins->getActiveSkin();

        $menuSections = $this->buildMenuSections();

        $initialState = [
            'user' => $this->getUserState(),
            'screens' => $this->buildScreens($menuSections),
            'menuSections' => $menuSections,
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

        return array_map(function (array $group) {
            $items = $group['children'] ?? [];

            return [
                'id' => $group['id'] ?? 'section-' . spl_object_id($group),
                'title' => $group['label'] ?? '',
                'priority' => $group['priority'] ?? 10,
                'items' => array_map(fn(array $child) => [
                    'id' => $child['id'] ?? $child['screenId'] ?? '',
                    'screenId' => $child['screenId'] ?? $child['id'] ?? '',
                    'label' => $child['label'] ?? '',
                    'icon' => $child['icon'] ?? 'Circle',
                    'url' => $child['url'] ?? '',
                    'priority' => $child['priority'] ?? 10,
                ], $items),
            ];
        }, $menuTree);
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
