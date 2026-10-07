<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\ClassicTheme\Transformers\Functions;

use PrestoWorld\Modules\ClassicTheme\Transformers\FunctionTransformer;

class RegisterNavMenusTransformer extends FunctionTransformer
{
    public function handles(): string
    {
        return 'register_nav_menus';
    }

    public function handle(mixed ...$args): mixed
    {
        return null;
    }
}
