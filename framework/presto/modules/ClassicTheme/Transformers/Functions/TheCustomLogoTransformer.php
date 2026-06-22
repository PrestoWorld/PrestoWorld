<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\ClassicTheme\Transformers\Functions;

use PrestoWorld\Modules\ClassicTheme\Transformers\FunctionTransformer;

class TheCustomLogoTransformer extends FunctionTransformer
{
    public function handles(): string
    {
        return 'the_custom_logo';
    }

    public function handle(mixed ...$args): mixed
    {
        echo call_user_func('get_custom_logo');
        return null;
    }
}
