<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\ClassicTheme\Transformers\Functions;

use PrestoWorld\Modules\ClassicTheme\Transformers\FunctionTransformer;

class BodyClassTransformer extends FunctionTransformer
{
    public function handles(): string
    {
        return 'body_class';
    }

    public function handle(mixed ...$args): mixed
    {
        echo 'class="page"';
        return null;
    }
}
