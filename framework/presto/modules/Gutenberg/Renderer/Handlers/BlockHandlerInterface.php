<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\Gutenberg\Renderer\Handlers;

/**
 * Interface for dedicated Block Handlers
 */
interface BlockHandlerInterface
{
    public function render(array $attrs, string $inner, array $block, array $context): string;
}
