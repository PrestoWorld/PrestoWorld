<?php

declare(strict_types=1);

namespace PrestoWorld\Contracts\Admin\ScreenOptions;

interface ScreenOption
{
    public function getId(): string;
    public function getLabel(): string;
    public function getType(): string;
    public function getDefault(): mixed;
    public function getOptions(): array;
    public function toArray(): array;
}
