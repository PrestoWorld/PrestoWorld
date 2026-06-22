<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\ClassicTheme\Transformers\Functions;

use PrestoWorld\Modules\ClassicTheme\Transformers\FunctionTransformer;

class HasCustomLogoTransformer extends FunctionTransformer
{
    public function handles(): string
    {
        return 'has_custom_logo';
    }

    public function handle(mixed ...$args): mixed
    {
        return false;
    }
}
