<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\ClassicTheme\Transformers\Functions;

use PrestoWorld\Modules\ClassicTheme\Transformers\FunctionTransformer;

class WpListCommentsTransformer extends FunctionTransformer
{
    public function handles(): string
    {
        return 'wp_list_comments';
    }

    public function handle(mixed ...$args): mixed
    {
        return null;
    }
}
