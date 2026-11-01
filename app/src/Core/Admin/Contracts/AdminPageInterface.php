<?php

declare(strict_types=1);

namespace PrestoWorld\Core\Admin\Contracts;

interface AdminPageInterface
{
    public function getId(): string;

    public function getSlug(): string;

    public function getTitle(): string;

    public function getMenuTitle(): string;

    public function getCapability(): string;

    public function getIcon(): ?string;

    public function getParentSlug(): ?string;

    public function getPosition(): int;

    public function getRenderCallback(): ?callable;

    public function getSpaComponent(): ?string;

    public function getAttributes(): array;
}
