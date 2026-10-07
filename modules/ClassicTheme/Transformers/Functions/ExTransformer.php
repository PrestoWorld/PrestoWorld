<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\ClassicTheme\Transformers\Functions;

use PrestoWorld\Modules\ClassicTheme\Transformers\FunctionTransformer;

class ExTransformer extends FunctionTransformer
{
    public function handles(): string
    {
        return '_ex';
    }

    public function handle(mixed ...$args): mixed
    {
        echo $args[0] ?? '';
        return null;
    }
}
