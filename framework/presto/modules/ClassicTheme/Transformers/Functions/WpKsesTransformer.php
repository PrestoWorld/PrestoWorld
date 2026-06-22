<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\ClassicTheme\Transformers\Functions;

use PrestoWorld\Modules\ClassicTheme\Transformers\FunctionTransformer;

class WpKsesTransformer extends FunctionTransformer
{
    public function handles(): string
    {
        return 'wp_kses';
    }

    public function handle(mixed ...$args): mixed
    {
        return $args[0] ?? '';
    }
}
