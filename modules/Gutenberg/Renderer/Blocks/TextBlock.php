<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\Gutenberg\Renderer\Blocks;

/**
 * Text Block (HTML fragments or standard text)
 */
class TextBlock extends AbstractBlock
{
    public function render(array $context): string
    {
        return $this->innerHTML;
    }
}
