<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\ClassicTheme\Transformers\Functions;

use PrestoWorld\Modules\ClassicTheme\Transformers\FunctionTransformer;

class WpDateTransformer extends FunctionTransformer
{
    public function handles(): string
    {
        return 'wp_date';
    }

    public function handle(mixed ...$args): mixed
    {
        return call_user_func('date_i18n', $args[0] ?? 'F j, Y', $args[1] ?? null);
    }
}
