<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\ClassicTheme\Transformers;

abstract class ClassTransformer implements TransformerInterface
{
    abstract public function define(): void;
}
