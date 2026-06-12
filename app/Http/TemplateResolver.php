<?php

declare(strict_types=1);

namespace App\Http;

use App\Contracts\Http\TemplateMappingPolicy;
use Witals\Framework\Http\Request;

class TemplateResolver
{
    public function __construct(
        private TemplateMappingPolicy $policy,
    ) {}

    public function resolve(Request $request): ?string
    {
        $path = rtrim($request->path(), '/');
        $normalized = $path === '' ? '/' : $path;

        return $this->policy->match($normalized);
    }
}
