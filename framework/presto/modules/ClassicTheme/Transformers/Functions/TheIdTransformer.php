<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\ClassicTheme\Transformers\Functions;

use PrestoWorld\Modules\ClassicTheme\Transformers\FunctionTransformer;

class TheIdTransformer extends FunctionTransformer
{
    public function handles(): string
    {
        return 'the_ID';
    }

    public function handle(mixed ...$args): mixed
    {
        echo call_user_func('get_the_ID');
        return null;
    }
}
