<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\Gutenberg\Renderer\Blocks;

class HtmlBlock extends AbstractBlock
{
    public function render(array $context): string
    {
        return $this->innerHTML;
    }
}
