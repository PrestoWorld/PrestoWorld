<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\ClassicTheme\Transformers\Functions;

use PrestoWorld\Modules\ClassicTheme\Transformers\FunctionTransformer;

class GetSearchQueryTransformer extends FunctionTransformer
{
    public function handles(): string
    {
        return 'get_search_query';
    }

    public function handle(mixed ...$args): mixed
    {
        return '';
    }
}
