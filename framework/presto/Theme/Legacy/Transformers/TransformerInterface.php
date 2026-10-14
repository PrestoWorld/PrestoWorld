<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\ClassicTheme\Transformers;

interface TransformerInterface
{
    public function handles(): string;
}
