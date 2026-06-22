<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\ClassicTheme\Transformers\Functions;

use PrestoWorld\Modules\ClassicTheme\Transformers\FunctionTransformer;

class IsHomeTransformer extends FunctionTransformer
{
    public function handles(): string
    {
        return 'is_home';
    }

    public function handle(mixed ...$args): mixed
    {
        return true;
    }
}
