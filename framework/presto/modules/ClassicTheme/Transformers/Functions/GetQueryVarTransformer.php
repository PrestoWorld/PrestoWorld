<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\ClassicTheme\Transformers\Functions;

use PrestoWorld\Modules\ClassicTheme\Transformers\FunctionTransformer;

class GetQueryVarTransformer extends FunctionTransformer
{
    public function handles(): string
    {
        return 'get_query_var';
    }

    public function handle(mixed ...$args): mixed
    {
        return '';
    }
}
