<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\Admin;

use PrestoWorld\Contracts\Admin\SkinInterface;
use InvalidArgumentException;

class SkinManager
{
    protected array $skins = [];
    protected array $skinMeta = [];
    protected ?string $activeSkin = null;

    public function registerSkin(SkinInterface $skin, ?array $metadata = null): void
    {
        $name = $skin->getName();
        $this->skins[$name] = $skin;

        if ($metadata !== null) {
            $this->skinMeta[$name] = $metadata;
        }

        if ($this->activeSkin === null) {
            $this->activeSkin = $name;
        }
    }

    public function registerFromManifest(string $skinClass, array $manifest): void
    {
        $name = $manifest['name'] ?? $skinClass;
        $this->skinMeta[$name] = $manifest;
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

    public function getSkinMeta(string $name): ?array
    {
        return $this->skinMeta[$name] ?? null;
    }

    public function getActiveSkinMeta(): ?array
    {
        if ($this->activeSkin === null) {
            return null;
        }
        return $this->skinMeta[$this->activeSkin] ?? null;
    }

    public function getSkins(): array
    {
        return $this->skins;
    }

    public function hasSkin(string $name): bool
    {
        return isset($this->skins[$name]);
    }

    public function getActiveRenderMode(): string
    {
        $skin = $this->getActiveSkin();
        return $skin !== null ? $skin->getRenderMode() : SkinInterface::MODE_SSR;
    }

    public function isCsrMode(): bool
    {
        return $this->getActiveRenderMode() === SkinInterface::MODE_CSR;
    }

    public function isSsrMode(): bool
    {
        return $this->getActiveRenderMode() === SkinInterface::MODE_SSR;
    }
}
