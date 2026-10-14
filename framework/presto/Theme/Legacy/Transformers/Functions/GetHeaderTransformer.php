<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\ClassicTheme\Transformers\Functions;

use PrestoWorld\Modules\ClassicTheme\Transformers\FunctionTransformer;

class GetHeaderTransformer extends FunctionTransformer
{
    public function handles(): string
    {
        return 'get_header';
    }

    public function handle(mixed ...$args): mixed
    {
        $name = isset($args[0]) ? (string) $args[0] : null;
        call_user_func('get_template_part', 'header', $name);
        return null;
    }
}
