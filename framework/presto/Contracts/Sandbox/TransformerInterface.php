<?php

declare(strict_types=1);

namespace PrestoWorld\Contracts\Sandbox;

interface TransformerInterface
{
    /**
     * Transform the source code into a new format.
     *
     * @param string $code The original source code.
     * @return string The transformed source code.
     */
    public function transform(string $code): string;

    /**
     * Get the keywords/functions this transformer targets.
     * Used for lazy loading and performance optimization.
     *
     * @return string[]
     */
    public function getKeywords(): array;
}
