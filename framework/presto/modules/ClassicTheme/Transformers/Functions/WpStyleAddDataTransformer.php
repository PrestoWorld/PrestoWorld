<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\ClassicTheme\Transformers\Functions;

use PrestoWorld\Modules\ClassicTheme\Transformers\FunctionTransformer;

class WpStyleAddDataTransformer extends FunctionTransformer
{
    public function handles(): string
    {
        return 'wp_style_add_data';
    }

    public function handle(mixed ...$args): mixed
    {
        return null;
    }
}
