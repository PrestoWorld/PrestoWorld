<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\ClassicTheme\Transformers\Functions;

use PrestoWorld\Modules\ClassicTheme\Transformers\FunctionTransformer;

class DoActionTransformer extends FunctionTransformer
{
    public function handles(): string
    {
        return 'do_action';
    }

    public function handle(mixed ...$args): mixed
    {
        return null;
    }
}
