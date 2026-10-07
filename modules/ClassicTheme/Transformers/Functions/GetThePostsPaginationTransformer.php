<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\ClassicTheme\Transformers\Functions;

use PrestoWorld\Modules\ClassicTheme\Transformers\FunctionTransformer;

class GetThePostsPaginationTransformer extends FunctionTransformer
{
    public function handles(): string
    {
        return 'get_the_posts_pagination';
    }

    public function handle(mixed ...$args): mixed
    {
        return '';
    }
}
