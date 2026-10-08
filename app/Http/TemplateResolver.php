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
        return $this->policy->match($this->normalize($request));
    }

    /**
     * True when the path matched an explicit rule in the mapping config
     * (i.e. it did not fall through to the default template).
     */
    public function matchesExplicitly(Request $request): bool
    {
        return $this->policy->isExplicit($this->normalize($request));
    }

    private function normalize(Request $request): string
    {
        $path = rtrim($request->path(), '/');

        return $path === '' ? '/' : $path;
    }
}
