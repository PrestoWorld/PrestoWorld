<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\ClassicTheme\Transformers\Functions;

use PrestoWorld\Modules\ClassicTheme\Transformers\FunctionTransformer;

class DateI18nTransformer extends FunctionTransformer
{
    public function handles(): string
    {
        return 'date_i18n';
    }

    public function handle(mixed ...$args): mixed
    {
        $format = (string) ($args[0] ?? 'F j, Y');
        $timestamp = isset($args[1]) ? (int) $args[1] : time();

        return date($format, $timestamp);
    }
}
