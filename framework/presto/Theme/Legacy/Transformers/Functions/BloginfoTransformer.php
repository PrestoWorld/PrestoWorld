<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\ClassicTheme\Transformers\Functions;

use PrestoWorld\Modules\ClassicTheme\Transformers\FunctionTransformer;

class BloginfoTransformer extends FunctionTransformer
{
    public function handles(): string
    {
        return 'bloginfo';
    }

    public function handle(mixed ...$args): mixed
    {
        echo call_user_func('get_bloginfo', $args[0] ?? '');
        return null;
    }
}
