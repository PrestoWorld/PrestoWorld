<?php

declare(strict_types=1);

namespace App\Http\Mappings;

use App\Contracts\Http\TemplateMappingPolicy;

class ConfigMappingPolicy implements TemplateMappingPolicy
{
    /**
     * @var list<array{type: string, pattern: string, template: string}>
     */
    private array $exactRules;

    /**
     * @var list<array{type: 'prefix', pattern: string, template: string}|array{type: 'wildcard', prefix: string, template: string}>
     */
    private array $prefixRules;

    private string $defaultTemplate;

    /**
     * @param array<string, string> $mapping
     */
    public function __construct(array $mapping, string $defaultTemplate = 'index')
    {
        $this->defaultTemplate = $defaultTemplate;
        $parsed = $this->compileRules($mapping);
        $this->exactRules = $parsed['exact'];
        $this->prefixRules = $parsed['prefix'];
    }

    public function match(string $path): string
    {
        return $this->findTemplate($path) ?? $this->defaultTemplate;
    }

    /**
     * Only exact and wildcard rules declare a known URL. Plain prefix rules
     * merely pick a template for child paths — content lookup decides whether
     * such a path actually exists (WordPress-style 404).
     */
    public function isExplicit(string $path): bool
    {
        foreach ($this->exactRules as $rule) {
            if ($path === $rule['pattern']) {
                return true;
            }
        }

        foreach ($this->prefixRules as $rule) {
            if ($rule['type'] === 'wildcard' && str_starts_with($path, $rule['prefix'])) {
                return true;
            }
        }

        return false;
    }

    private function findTemplate(string $path): ?string
    {
        foreach ($this->exactRules as $rule) {
            if ($path === $rule['pattern']) {
                return $rule['template'];
            }
        }

        foreach ($this->prefixRules as $rule) {
            if ($rule['type'] === 'wildcard' && str_starts_with($path, $rule['prefix'])) {
                return $rule['template'];
            }
            if ($rule['type'] === 'prefix' && $this->isPrefixMatch($path, $rule['pattern'])) {
                return $rule['template'];
            }
        }

        return null;
    }

    /**
     * @param array<string, string> $mapping
     * @return array{
     *     exact: list<array{type: string, pattern: string, template: string}>,
     *     prefix: list<array{type: 'prefix', pattern: string, template: string}|array{type: 'wildcard', prefix: string, template: string}>
     * }
     */
    private function compileRules(array $mapping): array
    {
        $exact = [];
        $prefix = [];
        foreach ($mapping as $pattern => $template) {
            if ($pattern === '/') {
                $exact[] = ['type' => 'exact', 'pattern' => '/', 'template' => $template];
            } elseif (str_ends_with($pattern, '/*')) {
                $wildPrefix = rtrim($pattern, '*');
                $prefix[] = ['type' => 'wildcard', 'prefix' => $wildPrefix, 'template' => $template];
                $exactPrefix = rtrim($wildPrefix, '/');
                if ($exactPrefix !== '') {
                    $exact[] = ['type' => 'exact', 'pattern' => $exactPrefix, 'template' => $template];
                }
            } else {
                $exact[] = ['type' => 'exact', 'pattern' => $pattern, 'template' => $template];
                $prefix[] = ['type' => 'prefix', 'pattern' => $pattern, 'template' => $template];
            }
        }

        usort(
            $prefix,
            static fn (array $a, array $b): int => strlen(self::ruleKey($b)) <=> strlen(self::ruleKey($a))
        );

        return ['exact' => $exact, 'prefix' => $prefix];
    }

    /**
     * @param array{type: 'prefix', pattern: string, template: string}|array{type: 'wildcard', prefix: string, template: string} $rule
     */
    private static function ruleKey(array $rule): string
    {
        return $rule['type'] === 'wildcard' ? $rule['prefix'] : $rule['pattern'];
    }

    private function isPrefixMatch(string $path, string $prefix): bool
    {
        return $prefix !== '/' && str_starts_with($path, $prefix . '/');
    }
}
