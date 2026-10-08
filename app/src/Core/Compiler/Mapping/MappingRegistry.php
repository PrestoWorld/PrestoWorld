<?php

declare(strict_types=1);

namespace PrestoWorld\Core\Compiler\Mapping;

use PrestoWorld\Core\Compiler\CompilerException;

/**
 * Master Mapping Registry (spec 10 §10.2.5, §10.8.1).
 *
 * Nguồn dữ liệu chuẩn: resources/mappings/master.json (sinh từ spec 10
 * bằng tools/generate-master-mapping.php). Bản phân phối qua Cloudflare
 * (§10.8.2) merge vào qua overlay() — entry cùng source của overlay thắng.
 *
 * @phpstan-type FunctionArray array{
 *     kind: 'function', source: string, target: string, mode: string,
 *     group?: string, wrap?: string, echo?: bool, throws?: bool, notes?: string
 * }
 * @phpstan-type ClassArray array{
 *     kind: 'class', source: string, target: string, mode: string,
 *     group?: string, structural?: array<string, string>, notes?: string
 * }
 * @phpstan-type SqlRuleArray array{
 *     kind: 'sql', pattern: string, replacement: string,
 *     target: string, apply: string, phase?: string, notes?: string
 * }
 * @phpstan-type WpdbRuleArray array{
 *     kind: 'wpdb', pattern: string, replacement: string,
 *     target: string, apply: string, phase?: string, notes?: string
 * }
 * @phpstan-type RuleArray FunctionArray|ClassArray|SqlRuleArray|WpdbRuleArray
 */
final class MappingRegistry
{
    /** @var array<string, FunctionMapping> key = strtolower(source) */
    private array $functions = [];

    /** @var array<string, ClassMapping> key = source FQN */
    private array $classes = [];

    /** @var list<QueryRule> */
    private array $queries = [];

    /** @var list<string> fnmatch globs cho unsupported functions (vd: mysqli_*) */
    private array $unsupportedPatterns = [];

    private string $version = '0.0.0';

    /**
     * @param array<string, mixed> $data decoded JSON theo format §10.8.1
     */
    private function __construct(array $data)
    {
        $this->hydrate($data);
    }

    /**
     * @throws CompilerException
     */
    public static function fromFile(string $path): self
    {
        if (!is_file($path)) {
            throw new CompilerException("Mapping file not found: {$path}");
        }

        $raw = file_get_contents($path);
        if ($raw === false) {
            throw new CompilerException("Unable to read mapping file: {$path}");
        }

        try {
            $decoded = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new CompilerException("Invalid mapping JSON in {$path}: " . $e->getMessage(), 0, $e);
        }

        if (!is_array($decoded)) {
            throw new CompilerException("Mapping file must contain a JSON object: {$path}");
        }

        /** @var array<string, mixed> $decoded */
        return new self($decoded);
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self($data);
    }

    /**
     * Merge một map phân phối (hotfix / map-version) vào registry hiện tại.
     * Entry cùng source trong overlay thay thế entry cũ (§10.8.2).
     */
    public function overlay(self $overlay): void
    {
        foreach ($overlay->functions as $key => $mapping) {
            $this->functions[$key] = $mapping;
        }

        foreach ($overlay->classes as $key => $mapping) {
            $this->classes[$key] = $mapping;
        }

        foreach ($overlay->queries as $rule) {
            $this->queries[] = $rule;
        }

        foreach ($overlay->unsupportedPatterns as $pattern) {
            $this->unsupportedPatterns[] = $pattern;
        }

        $this->version = $overlay->version;
    }

    public function version(): string
    {
        return $this->version;
    }

    public function functionFor(string $name): ?FunctionMapping
    {
        return $this->functions[strtolower(ltrim($name, '\\'))] ?? null;
    }

    public function classFor(string $name): ?ClassMapping
    {
        return $this->classes[ltrim($name, '\\')] ?? null;
    }

    /**
     * Kiểm tra theo glob patterns (unsupported entries chứa "*", vd mysqli_*).
     */
    public function isUnsupportedFunction(string $name): bool
    {
        $name = ltrim($name, '\\');

        foreach ($this->unsupportedPatterns as $pattern) {
            if (fnmatch($pattern, $name)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<QueryRule>
     */
    public function queryRules(?string $dialect = null, ?string $kind = null): array
    {
        $rules = [];

        foreach ($this->queries as $rule) {
            if ($kind !== null && $rule->kind !== $kind) {
                continue;
            }

            if ($dialect !== null && !$rule->appliesTo($dialect)) {
                continue;
            }

            $rules[] = $rule;
        }

        return $rules;
    }

    /**
     * @return array<string, FunctionMapping>
     */
    public function functions(): array
    {
        return $this->functions;
    }

    /**
     * @return array<string, ClassMapping>
     */
    public function classes(): array
    {
        return $this->classes;
    }

    /**
     * @return array{functions: int, classes: int, queries: int}
     */
    public function stats(): array
    {
        return [
            'functions' => count($this->functions),
            'classes' => count($this->classes),
            'queries' => count($this->queries),
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    private function hydrate(array $data): void
    {
        if (isset($data['version'])) {
            if (!is_string($data['version'])) {
                throw new CompilerException('Mapping "version" must be a string');
            }
            $this->version = $data['version'];
        }

        $rules = $data['rules'] ?? [];
        if (!is_array($rules)) {
            throw new CompilerException('Mapping "rules" must be an array');
        }

        foreach ($rules as $index => $rule) {
            if (!is_array($rule)) {
                throw new CompilerException("Mapping rule #{$index} must be an object");
            }

            /** @var array<string, mixed> $rule */
            $kind = $rule['kind'] ?? null;

            switch ($kind) {
                case 'function':
                    $this->registerFunction($rule);
                    break;
                case 'class':
                    $this->registerClass($rule);
                    break;
                case 'sql':
                case 'wpdb':
                    $this->queries[] = $this->createQueryRule($rule, (string) $index);
                    break;
                default:
                    throw new CompilerException("Mapping rule #{$index} has unknown kind " . var_export($kind, true));
            }
        }
    }

    /**
     * @param array<string, mixed> $rule
     */
    private function registerFunction(array $rule): void
    {
        $source = $this->requireString($rule, 'source');
        $mode = $this->requireString($rule, 'mode');
        $target = isset($rule['target']) && is_string($rule['target']) ? $rule['target'] : '';

        if (str_contains($source, '*')) {
            if ($mode !== FunctionMapping::MODE_UNSUPPORTED) {
                throw new CompilerException("Pattern function mapping \"{$source}\" must be mode x");
            }
            $this->unsupportedPatterns[] = str_replace('.*', '*', $source);
            return;
        }

        $mapping = new FunctionMapping(
            source: $source,
            target: $target,
            mode: $mode,
            group: $this->optionalString($rule, 'group'),
            wrap: isset($rule['wrap']) && is_string($rule['wrap']) ? $rule['wrap'] : null,
            echo: (bool) ($rule['echo'] ?? false),
            throws: (bool) ($rule['throws'] ?? false),
            notes: isset($rule['notes']) && is_string($rule['notes']) ? $rule['notes'] : null,
        );

        $this->functions[strtolower($source)] = $mapping;
    }

    /**
     * @param array<string, mixed> $rule
     */
    private function registerClass(array $rule): void
    {
        $source = $this->requireString($rule, 'source');
        $mode = $this->requireString($rule, 'mode');
        $target = isset($rule['target']) && is_string($rule['target']) ? $rule['target'] : '';

        $structural = [];
        if (isset($rule['structural'])) {
            if (!is_array($rule['structural'])) {
                throw new CompilerException("Class mapping \"{$source}\" structural must be an object");
            }
            foreach ($rule['structural'] as $key => $value) {
                if (is_string($key) && is_string($value)) {
                    $structural[$key] = $value;
                }
            }
        }

        $mapping = new ClassMapping(
            source: $source,
            target: $target,
            mode: $mode,
            structural: $structural,
            group: $this->optionalString($rule, 'group'),
            notes: isset($rule['notes']) && is_string($rule['notes']) ? $rule['notes'] : null,
        );

        $this->classes[ltrim($source, '\\')] = $mapping;
    }

    /**
     * @param array<string, mixed> $rule
     */
    private function createQueryRule(array $rule, string $index): QueryRule
    {
        $replacement = $rule['replacement'] ?? null;
        if (!is_string($replacement)) {
            throw new CompilerException("Mapping rule missing required string field \"replacement\"");
        }

        return new QueryRule(
            kind: $this->requireString($rule, 'kind'),
            pattern: $this->requireString($rule, 'pattern'),
            replacement: $replacement,
            target: $this->requireString($rule, 'target'),
            apply: $this->requireString($rule, 'apply'),
            phase: $this->optionalString($rule, 'phase', 'dml'),
        );
    }

    /**
     * @param array<string, mixed> $rule
     */
    private function requireString(array $rule, string $key): string
    {
        $value = $rule[$key] ?? null;
        if (!is_string($value) || $value === '') {
            throw new CompilerException("Mapping rule missing required string field \"{$key}\"");
        }

        return $value;
    }

    /**
     * @param array<string, mixed> $rule
     */
    private function optionalString(array $rule, string $key, string $default = ''): string
    {
        $value = $rule[$key] ?? null;

        return is_string($value) ? $value : $default;
    }
}
