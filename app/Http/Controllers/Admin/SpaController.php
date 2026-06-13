<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use Witals\Framework\Http\Request;
use Witals\Framework\Http\Response;
use PrestoWorld\Modules\Admin\SkinManager;
use PrestoWorld\Contracts\Admin\Menu\MenuContextRepository;

class SpaController
{
    public function __construct(
        protected SkinManager $skins,
        protected MenuContextRepository $menu,
    ) {}

    public function __invoke(Request $request): Response
    {
        $skin = $this->skins->getActiveSkin();

        $initialState = [
            'user' => $this->getUserState(),
            'menu' => $this->menu->getTreeAsArray(),
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
