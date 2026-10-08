<?php

declare(strict_types=1);

namespace PrestoWorld\Core\Database;

use PrestoWorld\Core\Compiler\Mapping\QueryRule;

/**
 * Runtime SQL Transformer (spec 10 §10.3 — "apply: runtime|both").
 *
 * Biến đổi câu SQL kiểu MySQL sang dialect đích (mặc định PostgreSQL) theo
 * bộ QueryRule canonical trong master.json. Chỉ áp dụng các rule khớp dialect.
 */
final class QueryTransformer
{
    /** @var list<QueryRule> */
    private array $rules = [];

    public function __construct(private readonly string $dialect = QueryRule::TARGET_POSTGRESQL)
    {
    }

    public function dialect(): string
    {
        return $this->dialect;
    }

    public function addRule(QueryRule $rule): self
    {
        if ($rule->kind !== QueryRule::KIND_SQL) {
            return $this;
        }

        $this->rules[] = $rule;
        return $this;
    }

    /** @param list<QueryRule> $rules */
    public function addRules(array $rules): self
    {
        foreach ($rules as $rule) {
            $this->addRule($rule);
        }
        return $this;
    }

    /**
     * Nạp rule runtime từ mapping JSON (resources/mappings/master.json).
     * Apply hệ quả: runtime|both; dialect khớp.
     */
    public static function fromMappingFile(string $jsonFile, string $dialect = QueryRule::TARGET_POSTGRESQL): self
    {
        $raw = file_get_contents($jsonFile);
        if ($raw === false) {
            return new self($dialect);
        }

        try {
            /** @var array{rules?: list<array<string, mixed>>} $mapping */
            $mapping = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return new self($dialect);
        }

        $transformer = new self($dialect);

        foreach ($mapping['rules'] ?? [] as $entry) {
            if (($entry['kind'] ?? null) !== QueryRule::KIND_SQL) {
                continue;
            }

            $rule = new QueryRule(
                kind: QueryRule::KIND_SQL,
                pattern: (string) ($entry['pattern'] ?? ''),
                replacement: (string) ($entry['replacement'] ?? ''),
                target: (string) ($entry['target'] ?? QueryRule::TARGET_ANY),
                apply: (string) ($entry['apply'] ?? QueryRule::APPLY_COMPILE),
                phase: (string) ($entry['phase'] ?? 'dml'),
            );

            if ($rule->appliesAtRuntime()) {
                $transformer->addRule($rule);
            }
        }

        return $transformer;
    }

    /**
     * Biến đổi câu SQL sang dialect đích. Nếu không có rule/không áp dụng
     * được thì trả nguyên bản.
     */
    public function transform(string $sql): string
    {
        $out = $sql;

        foreach ($this->rules as $rule) {
            if (!$rule->appliesTo($this->dialect) || !$rule->appliesAtRuntime()) {
                continue;
            }

            $out = (string) preg_replace($rule->pattern, $rule->replacement, $out);
        }

        return $out;
    }

    /** @return list<QueryRule> */
    public function applicableRules(): array
    {
        $result = [];
        foreach ($this->rules as $rule) {
            if ($rule->appliesTo($this->dialect) && $rule->appliesAtRuntime()) {
                $result[] = $rule;
            }
        }
        return $result;
    }
}