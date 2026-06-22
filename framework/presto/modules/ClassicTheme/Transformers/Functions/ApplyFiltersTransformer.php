<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\ClassicTheme\Transformers\Functions;

use PrestoWorld\Modules\ClassicTheme\Transformers\FunctionTransformer;

class ApplyFiltersTransformer extends FunctionTransformer
{
    public function handles(): string
    {
        return 'apply_filters';
    }

    public function handle(mixed ...$args): mixed
    {
        return $args[1] ?? null;
    }
}
