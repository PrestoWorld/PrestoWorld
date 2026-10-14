<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\ClassicTheme\Transformers\Functions;

use PrestoWorld\Modules\ClassicTheme\Transformers\FunctionTransformer;

class AdminUrlTransformer extends FunctionTransformer
{
    public function handles(): string
    {
        return 'admin_url';
    }

    public function handle(mixed ...$args): mixed
    {
        $path = $args[0] ?? '';
        return '/wp-admin/' . ltrim($path, '/');
    }
}
