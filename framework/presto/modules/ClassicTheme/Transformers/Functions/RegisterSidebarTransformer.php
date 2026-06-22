<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\ClassicTheme\Transformers\Functions;

use PrestoWorld\Modules\ClassicTheme\Transformers\FunctionTransformer;

class RegisterSidebarTransformer extends FunctionTransformer
{
    public function handles(): string
    {
        return 'register_sidebar';
    }

    public function handle(mixed ...$args): mixed
    {
        return null;
    }
}
