<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use Witals\Framework\Http\Request;
use Witals\Framework\Http\Response;
use PrestoWorld\Modules\Admin\SkinManager;
use PrestoWorld\Contracts\Admin\Menu\MenuContextRepository;
use PrestoWorld\Contracts\Admin\Dashboard\DashboardWidgetRepository;

class SpaController
{
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
            'menu' => $this->menu->getTreeAsArray(),
            'widgets' => $this->widgets->getWidgetsGroupedByColumn('dashboard'),
            'page' => [
                'path' => $request->path(),
                'title' => 'Dashboard',
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
            'menu' => $this->menu->getTreeAsArray(),
        ]);
    }

    public function dashboardWidgets(Request $request): Response
    {
        $columns = [];
        foreach ($this->widgets->getWidgetsGroupedByColumn('dashboard') as $col => $widgets) {
            $columns[$col] = array_map(fn($w) => $w->toArray(), $widgets);
        }

        return Response::json($columns);
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

    protected function getUserState(): array
    {
        return [
            'name' => 'Administrator',
            'role' => 'admin',
            'avatar' => null,
        ];
    }
}
