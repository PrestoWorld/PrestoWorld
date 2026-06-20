<?php

declare(strict_types=1);

namespace PrestoWorld\Contracts\Admin\ScreenOptions;

interface ScreenOptionsContext
{
    public function getScreenId(): string;
    public function getTitle(): string;
    public function getOptions(): array;
    public function addOption(ScreenOption $option): void;
    public function toArray(): array;
}
