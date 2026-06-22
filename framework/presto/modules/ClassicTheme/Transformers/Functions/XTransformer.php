<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\ClassicTheme\Transformers\Functions;

use PrestoWorld\Modules\ClassicTheme\Transformers\FunctionTransformer;

class XTransformer extends FunctionTransformer
{
    public function handles(): string
    {
        return '_x';
    }

    public function handle(mixed ...$args): mixed
    {
        return $args[0] ?? '';
    }
}
