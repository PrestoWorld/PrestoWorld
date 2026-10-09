<?php

declare(strict_types=1);

namespace PrestoWorld\Core\Term;

use PrestoWorld\Core\TermRepository;
use PrestoWorld\Core\Term\TermEntity;

/**
 * TermQuery — WP_Term_Query replacement (spec 10 §10.5.3).
 */
final class TermQuery
{
    /** @var array<string, mixed> */
    private array $args = [];

    /** @param array<string, mixed> $args */
    public function __construct(array $args = [])
    {
        $this->args = $args;
    }

    /** @return list<TermEntity> */
    public function terms(): array
    {
        return TermRepository::query($this->args);
    }

    public function count(): int
    {
        return count($this->terms());
    }

    public function hasTerms(): bool
    {
        return $this->terms() !== [];
    }

    /** @return array<string, mixed> */
    public function getArgs(): array
    {
        return $this->args;
    }
}
