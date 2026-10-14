<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\ClassicTheme\Transformers\Functions;

use PrestoWorld\Modules\ClassicTheme\Transformers\FunctionTransformer;

class WpJsonEncodeTransformer extends FunctionTransformer
{
    public function handles(): string
    {
        return 'wp_json_encode';
    }

    public function handle(mixed ...$args): mixed
    {
        return json_encode($args[0] ?? [], (int) ($args[1] ?? 0), (int) ($args[2] ?? 512));
    }
}
