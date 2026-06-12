<?php

declare(strict_types=1);

namespace App\Http\Mappings;

use App\Contracts\Http\TemplateMappingPolicy;

class ConfigMappingPolicy implements TemplateMappingPolicy
{
    private array $rules;
    private string $defaultTemplate;

    public function __construct(array $mapping, string $defaultTemplate = 'index')
    {
        $this->defaultTemplate = $defaultTemplate;
        $this->rules = $this->compileRules($mapping);
    }

    public function match(string $path): ?string
    {
        foreach ($this->rules as $rule) {
            if ($rule['type'] === 'exact' && $path === $rule['pattern']) {
                return $rule['template'];
            }

            if ($rule['type'] === 'wildcard' && str_starts_with($path, $rule['prefix'])) {
                return $rule['template'];
            }

            if ($rule['type'] === 'prefix' && $this->isPrefixMatch($path, $rule['pattern'])) {
                return $rule['template'];
            }
        }

        return $this->defaultTemplate;
    }

    private function compileRules(array $mapping): array
    {
        $rules = [];
        foreach ($mapping as $pattern => $template) {
            if ($pattern === '/') {
                $rules[] = ['type' => 'exact', 'pattern' => $pattern, 'template' => $template];
            } elseif (str_ends_with($pattern, '/*')) {
                $prefix = rtrim($pattern, '*');
                $rules[] = ['type' => 'wildcard', 'prefix' => $prefix, 'template' => $template];
                // Also add an exact match for the prefix itself (e.g., /category/ matches /category/)
                $exactPrefix = rtrim($prefix, '/');
                if ($exactPrefix !== '') {
                    $rules[] = ['type' => 'exact', 'pattern' => $exactPrefix, 'template' => $template];
                }
            } else {
                // Non-root, non-wildcard: can match exact and as prefix
                $rules[] = ['type' => 'exact', 'pattern' => $pattern, 'template' => $template];
                $rules[] = ['type' => 'prefix', 'pattern' => $pattern, 'template' => $template];
            }
        }
        return $rules;
    }

    private function isPrefixMatch(string $path, string $prefix): bool
    {
        return $prefix !== '/' && str_starts_with($path, $prefix . '/');
    }
}
