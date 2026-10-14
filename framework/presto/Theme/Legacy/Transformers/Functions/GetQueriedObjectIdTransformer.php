<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\ClassicTheme\Transformers\Functions;

use PrestoWorld\Modules\ClassicTheme\Transformers\FunctionTransformer;

class GetQueriedObjectIdTransformer extends FunctionTransformer
{
    public function handles(): string
    {
        return 'get_queried_object_id';
    }

    public function handle(mixed ...$args): mixed
    {
        return 0;
    }
}
