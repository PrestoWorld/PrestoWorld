<?php

declare(strict_types=1);

namespace PrestoWorld\Core\Admin\Contracts;

interface AdminContextInterface
{
    public function isWpAdmin(): bool;

    public function isSpaDashboard(): bool;

    public function getMode(): string;
}
