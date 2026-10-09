<?php

declare(strict_types=1);

namespace PrestoWorld\Core\Taxonomy;

/**
 * TaxQuery — WP_Tax_Query replacement (spec 10 §10.5.3).
 */
final class TaxQuery
{
    /** @var array<int, array<string, mixed>> */
    private array $clauses = [];

    private string $relation = 'AND';

    /** @param array<string, mixed> $args */
    public function __construct(array $args = [])
    {
        $raw = $args;
        if (isset($args['tax_query']) && is_array($args['tax_query'])) {
            $raw = $args['tax_query'];
        }

        foreach ($raw as $clause) {
            if (is_array($clause)) {
                $this->clauses[] = self::stringKeys($clause);
            }
        }

        if (isset($raw['relation']) && is_scalar($raw['relation'])) {
            $this->relation = strtoupper((string) $raw['relation']);
        }
    }

    public function isEmpty(): bool
    {
        return $this->clauses === [];
    }

    /** @return array<int, array<string, mixed>> */
    public function clauses(): array
    {
        return $this->clauses;
    }

    public function sql(): string
    {
        return '';
    }

    public function relation(): string
    {
        return $this->relation;
    }

    /**
     * @param array<int|string, mixed> $input
     * @return array<string, mixed>
     */
    private static function stringKeys(array $input): array
    {
        $result = [];
        foreach ($input as $key => $value) {
            $result[(string) $key] = $value;
        }

        return $result;
    }
}
