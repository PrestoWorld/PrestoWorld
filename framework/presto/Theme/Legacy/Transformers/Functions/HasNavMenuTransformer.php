<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\ClassicTheme\Transformers\Functions;

use PrestoWorld\Modules\ClassicTheme\Transformers\FunctionTransformer;

class HasNavMenuTransformer extends FunctionTransformer
{
    public function handles(): string
    {
        return 'has_nav_menu';
    }

    public function handle(mixed ...$args): mixed
    {
        return false;
    }
}
