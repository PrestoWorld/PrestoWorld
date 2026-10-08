<?php

declare(strict_types=1);

namespace App\Contracts\Http;

interface TemplateMappingPolicy
{
    public function match(string $path): ?string;

    /**
     * Whether the path matched an explicit mapping rule (not the default template).
     */
    public function isExplicit(string $path): bool;
}
