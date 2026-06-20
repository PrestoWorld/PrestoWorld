<?php

declare(strict_types=1);

namespace PrestoWorld\Contracts\Admin\Screen;

interface ScreenInterface
{
    public function getId(): string;
    public function getTitle(): string;
    public function getParent(): ?string;
    public function getCapability(): ?string;
    public function getIcon(): ?string;
    public function getPosition(): int;
    public function toArray(): array;
}
