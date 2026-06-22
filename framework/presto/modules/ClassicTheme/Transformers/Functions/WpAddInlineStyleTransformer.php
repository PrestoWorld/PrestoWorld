<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\ClassicTheme\Transformers\Functions;

use PrestoWorld\Modules\ClassicTheme\Transformers\FunctionTransformer;

class WpAddInlineStyleTransformer extends FunctionTransformer
{
    public function handles(): string
    {
        return 'wp_add_inline_style';
    }

    public function handle(mixed ...$args): mixed
    {
        return null;
    }
}
