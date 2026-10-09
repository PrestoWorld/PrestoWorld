<?php

declare(strict_types=1);

namespace PrestoWorld\Core\User;

/**
 * Role — WP_Role replacement (spec 10 §10.5.5).
 */
class Role
{
    /** @var array<string, bool> */
    public array $capabilities = [];

    public int $level = 0;

    public function __construct(
        public string $name = '',
        public string $label = '',
    ) {
    }

    public function hasCap(string $cap): bool
    {
        return ($this->capabilities[$cap] ?? false) === true;
    }

    public function addCap(string $cap, bool $grant = true): void
    {
        $this->capabilities[$cap] = $grant;
    }

    public function removeCap(string $cap): void
    {
        unset($this->capabilities[$cap]);
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'label' => $this->label,
            'capabilities' => $this->capabilities,
            'level' => $this->level,
        ];
    }
}
