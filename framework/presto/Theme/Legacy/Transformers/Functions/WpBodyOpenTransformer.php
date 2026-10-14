<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\ClassicTheme\Transformers\Functions;

use PrestoWorld\Modules\ClassicTheme\Transformers\FunctionTransformer;

class WpBodyOpenTransformer extends FunctionTransformer
{
    public function handles(): string
    {
        return 'wp_body_open';
    }

    public function handle(mixed ...$args): mixed
    {
        return null;
    }
}
