<?php

declare(strict_types=1);

namespace PrestoWorld\Admin;

class MenuRepository
{
    /** @var Menu[] */
    protected array $menus = [];
    
    /** @var array<string, SubMenu[]> */
    protected array $subMenus = [];

    public function addMenu(Menu $menu): void
    {
        $this->menus[$menu->menuSlug] = $menu;
    }

    public function addSubMenu(SubMenu $subMenu): void
    {
        $this->subMenus[$subMenu->parentSlug][] = $subMenu;
    }

    /**
     * @return Menu[]
     */
    public function getMenus(): array
    {
        $menus = $this->menus;
        uasort($menus, function($a, $b) {
            $posA = $a->position ?? 999;
            $posB = $b->position ?? 999;
            return $posA <=> $posB;
        });
        return $menus;
    }

    /**
     * @return SubMenu[]
     */
    public function getSubMenus(string $parentSlug): array
    {
        $subs = $this->subMenus[$parentSlug] ?? [];
        usort($subs, function($a, $b) {
            $posA = $a->position ?? 999;
            $posB = $b->position ?? 999;
            return $posA <=> $posB;
        });
        return $subs;
    }
}
