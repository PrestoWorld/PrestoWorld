<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\ClassicTheme\Transformers\Functions;

use PrestoWorld\Modules\ClassicTheme\Transformers\FunctionTransformer;

class CommentFormTransformer extends FunctionTransformer
{
    public function handles(): string
    {
        return 'comment_form';
    }

    public function handle(mixed ...$args): mixed
    {
        return null;
    }
}
