<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\Gutenberg\Blocks;

/**
 * Pure Block Entity - Plain data structure for Gutenberg
 */
class Block
{
    public function __construct(
        public ?string $name = null,
        public array $attrs = [],
        public array $innerBlocks = [],
        public string $innerHTML = ''
    ) {}
}
