<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\ClassicTheme\Transformers\Functions;

use PrestoWorld\Modules\ClassicTheme\Transformers\FunctionTransformer;

class AddFilterTransformer extends FunctionTransformer
{
    public function handles(): string
    {
        return 'add_filter';
    }

    public function handle(mixed ...$args): mixed
    {
        return null;
    }
}
