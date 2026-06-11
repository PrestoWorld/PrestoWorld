<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\Gutenberg\Blocks\Contracts;

/**
 * Interface for frontend rendering logic
 */
interface RenderableBlockInterface
{
    public function render(array $context): string;
}

/**
 * Interface for Admin-side CRUD and Metadata
 */
interface EditableBlockInterface
{
    public function getMetadata(): array;
    public function save(): bool;
}
