<?php

declare(strict_types=1);

namespace PrestoWorld\Admin;

class Menu
{
    public function __construct(
        public string $pageTitle,
        public string $menuTitle,
        public string $capability,
        public string $menuSlug,
        public mixed $callback = null,
        public string $iconUrl = '',
        public ?int $position = null
    ) {}
}
