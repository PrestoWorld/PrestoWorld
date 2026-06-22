<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\ClassicTheme\Transformers\Functions;

use PrestoWorld\Modules\ClassicTheme\Transformers\FunctionTransformer;

class GetHomeUrlTransformer extends FunctionTransformer
{
    public function handles(): string
    {
        return 'get_home_url';
    }

    public function handle(mixed ...$args): mixed
    {
        $path = $args[1] ?? '';
        return '/' . ltrim($path, '/');
    }
}
