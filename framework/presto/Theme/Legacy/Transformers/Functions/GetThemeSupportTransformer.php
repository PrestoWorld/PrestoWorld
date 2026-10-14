<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\ClassicTheme\Transformers\Functions;

use PrestoWorld\Modules\ClassicTheme\Transformers\FunctionTransformer;

class GetThemeSupportTransformer extends FunctionTransformer
{
    public function handles(): string
    {
        return 'get_theme_support';
    }

    public function handle(mixed ...$args): mixed
    {
        return false;
    }
}
