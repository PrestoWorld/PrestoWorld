<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\ClassicTheme\Transformers\Functions;

use PrestoWorld\Modules\ClassicTheme\Transformers\FunctionTransformer;

class SiteUrlTransformer extends FunctionTransformer
{
    public function handles(): string
    {
        return 'site_url';
    }

    public function handle(mixed ...$args): mixed
    {
        $path = $args[0] ?? '';
        return '/' . ltrim($path, '/');
    }
}
