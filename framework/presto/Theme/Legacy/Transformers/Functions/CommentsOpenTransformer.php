<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\ClassicTheme\Transformers\Functions;

use PrestoWorld\Modules\ClassicTheme\Transformers\FunctionTransformer;

class CommentsOpenTransformer extends FunctionTransformer
{
    public function handles(): string
    {
        return 'comments_open';
    }

    public function handle(mixed ...$args): mixed
    {
        return false;
    }
}
