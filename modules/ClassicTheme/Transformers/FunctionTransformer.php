<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\ClassicTheme\Transformers;

abstract class FunctionTransformer implements TransformerInterface
{
    abstract public function handle(mixed ...$args): mixed;
}
