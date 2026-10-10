<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\HeadlessCMS\Transformer;

use PrestoWorld\Modules\HeadlessCMS\Serializer\MenuSerializer;

class MenuTransformer
{
    /**
     * Transform a single menu
     */
    public function transform(array $menu): array
    {
        return [
            'id' => (int) $menu['id'],
            'name' => $menu['name'] ?? '',
            'slug' => $menu['slug'] ?? '',
            'attributes' => [
                'name' => $menu['name'] ?? '',
                'description' => $menu['description'] ?? '',
            ],
            'relationships' => [
                'items' => $menu['items'] ?? [],
            ],
        ];
    }

    /**
     * Transform collection of menus
     */
    public function transformCollection(array $menus): array
    {
        return array_map(fn($menu) => $this->transform($menu), $menus);
    }
}