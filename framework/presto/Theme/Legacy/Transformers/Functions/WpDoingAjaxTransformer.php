<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\ClassicTheme\Transformers\Functions;

use PrestoWorld\Modules\ClassicTheme\Transformers\FunctionTransformer;

class WpDoingAjaxTransformer extends FunctionTransformer
{
    public function handles(): string
    {
        return 'wp_doing_ajax';
    }

    public function handle(mixed ...$args): mixed
    {
        return false;
    }
}
