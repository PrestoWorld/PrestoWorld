<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\Gutenberg\Renderer\Decorators;

/**
 * Interface for Gutenberg Block Decorators
 */
interface BlockDecoratorInterface
{
    public function decorate(array &$block): void;
}
