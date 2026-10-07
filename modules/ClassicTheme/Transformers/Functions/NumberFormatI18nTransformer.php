<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\ClassicTheme\Transformers\Functions;

use PrestoWorld\Modules\ClassicTheme\Transformers\FunctionTransformer;

class NumberFormatI18nTransformer extends FunctionTransformer
{
    public function handles(): string
    {
        return 'number_format_i18n';
    }

    public function handle(mixed ...$args): mixed
    {
        return number_format((float) ($args[0] ?? 0), (int) ($args[1] ?? 0));
    }
}
