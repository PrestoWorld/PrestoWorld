<?php

declare(strict_types=1);

namespace PrestoWorld\Admin\UI;

use PrestoWorld\Contracts\Admin\SkinInterface;
use InvalidArgumentException;

class SkinManager
{
    /** @var array<string, SkinInterface> */
    protected array $skins = [];

    protected ?string $activeSkin = null;

    public function registerSkin(SkinInterface $skin): void
    {
        $this->skins[$skin->getName()] = $skin;
        
        if ($this->activeSkin === null) {
            $this->activeSkin = $skin->getName();
        }
    }

    public function setActiveSkin(string $name): void
    {
        if (!isset($this->skins[$name])) {
            throw new InvalidArgumentException("Skin [{$name}] is not registered.");
        }
        $this->activeSkin = $name;
    }

    public function getActiveSkin(): ?SkinInterface
    {
        return $this->skins[$this->activeSkin] ?? null;
    }

    public function getSkin(string $name): ?SkinInterface
    {
        return $this->skins[$name] ?? null;
    }
}
