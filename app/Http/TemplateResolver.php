<?php

declare(strict_types=1);

namespace App\Http;

use Witals\Framework\Http\Request;

class TemplateResolver
{
    private array $mapping;
    private string $defaultTemplate;

    public function __construct(array $mapping, string $defaultTemplate = 'index')
    {
        $this->mapping = $mapping;
        $this->defaultTemplate = $defaultTemplate;
    }

    public function resolve(Request $request): string
    {
        $path = rtrim($request->path(), '/');
        $normalized = $path === '' ? '/' : $path;

        foreach ($this->mapping as $pattern => $template) {
            if ($normalized === $pattern) {
                return $template;
            }

            if (str_ends_with($pattern, '/*') && str_starts_with($normalized, rtrim($pattern, '*'))) {
                return $template;
            }

            if (!$this->isWildcard($pattern) && $this->isPrefixMatch($normalized, $pattern)) {
                return $template;
            }
        }

        return $this->defaultTemplate;
    }

    private function isWildcard(string $pattern): bool
    {
        return str_ends_with($pattern, '/*');
    }

    private function isPrefixMatch(string $path, string $prefix): bool
    {
        return $prefix !== '/' && str_starts_with($path, $prefix . '/');
    }
}
