<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\ClassicTheme\Transformers\Functions;

use PrestoWorld\Modules\ClassicTheme\Transformers\FunctionTransformer;

class IsFrontPageTransformer extends FunctionTransformer
{
    public function handles(): string
    {
        return 'is_front_page';
    }

    public function handle(mixed ...$args): mixed
    {
        return true;
    }
}
