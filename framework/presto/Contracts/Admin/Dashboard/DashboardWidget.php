<?php

declare(strict_types=1);

namespace PrestoWorld\Contracts\Admin\Dashboard;

interface DashboardWidget
{
    public function getId(): string;
    public function getTitle(): string;
    public function getContent(): string;
    public function getPriority(): int;
    public function getColumn(): int;
    public function getContext(): string;
    public function isVisible(): bool;
    public function toArray(): array;
}
