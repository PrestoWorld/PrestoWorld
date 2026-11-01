<?php

declare(strict_types=1);

namespace PrestoWorld\Core\Admin\Registry;

use PrestoWorld\Core\Admin\Contracts\AdminPageInterface;
use PrestoWorld\Core\Admin\Contracts\AdminRegistryInterface;

class AdminPageRegistry implements AdminRegistryInterface
{
    /** @var array<string, AdminPageInterface> */
    protected array $pages = [];

    public function registerPage(AdminPageInterface $page): void
    {
        $this->pages[$page->getSlug()] = $page;
    }

    public function getPage(string $slug): ?AdminPageInterface
    {
        return $this->pages[$slug] ?? null;
    }

    public function getPages(): array
    {
        return $this->pages;
    }

    public function getMenuTree(): array
    {
        $items = array_values($this->pages);
        usort($items, function (AdminPageInterface $a, AdminPageInterface $b) {
            return $a->getPosition() <=> $b->getPosition();
        });

        $tree = [];
        foreach ($items as $item) {
            $parent = $item->getParentSlug();
            if ($parent === null || $parent === '') {
                $tree[] = [
                    'id' => $item->getId(),
                    'slug' => $item->getSlug(),
                    'label' => $item->getMenuTitle(),
                    'title' => $item->getTitle(),
                    'icon' => $item->getIcon(),
                    'capability' => $item->getCapability(),
                    'position' => $item->getPosition(),
                    'renderCallback' => $item->getRenderCallback(),
                    'spaComponent' => $item->getSpaComponent(),
                    'attributes' => $item->getAttributes(),
                    'children' => [],
                ];
            } else {
                // attach as child to existing parent if found
                foreach ($tree as &$node) {
                    if (($node['slug'] ?? null) === $parent || ($node['id'] ?? null) === $parent) {
                        $node['children'][] = [
                            'id' => $item->getId(),
                            'slug' => $item->getSlug(),
                            'label' => $item->getMenuTitle(),
                            'title' => $item->getTitle(),
                            'icon' => $item->getIcon(),
                            'capability' => $item->getCapability(),
                            'position' => $item->getPosition(),
                            'renderCallback' => $item->getRenderCallback(),
                            'spaComponent' => $item->getSpaComponent(),
                            'attributes' => $item->getAttributes(),
                        ];
                        break;
                    }
                }
                unset($node);
            }
        }

        return $tree;
    }
}
