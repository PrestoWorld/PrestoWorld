<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\ClassicTheme\Transformers\Functions;

use PrestoWorld\Modules\ClassicTheme\Transformers\FunctionTransformer;

class AddThemeSupportTransformer extends FunctionTransformer
{
    public function handles(): string
    {
        return 'add_theme_support';
    }

    public function handle(mixed ...$args): mixed
    {
        return null;
    }
}
