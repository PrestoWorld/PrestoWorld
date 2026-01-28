<?php

declare(strict_types=1);

namespace PrestoWorld\Admin;

class SubMenu
{
    public function __construct(
        public string $parentSlug,
        public string $pageTitle,
        public string $menuTitle,
        public string $capability,
        public string $menuSlug,
        public mixed $callback = null,
        public ?int $position = null
    ) {}
}
