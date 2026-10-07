<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\ClassicTheme\Transformers\Functions;

use PrestoWorld\Modules\ClassicTheme\Transformers\FunctionTransformer;

class HomeUrlTransformer extends FunctionTransformer
{
    public function handles(): string
    {
        return 'home_url';
    }

    public function handle(mixed ...$args): mixed
    {
        $path = $args[0] ?? '';
        return '/' . ltrim($path, '/');
    }
}
