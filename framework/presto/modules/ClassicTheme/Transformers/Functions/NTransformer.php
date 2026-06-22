<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\ClassicTheme\Transformers\Functions;

use PrestoWorld\Modules\ClassicTheme\Transformers\FunctionTransformer;

class NTransformer extends FunctionTransformer
{
    public function handles(): string
    {
        return '_n';
    }

    public function handle(mixed ...$args): mixed
    {
        $single = (string) ($args[0] ?? '');
        $plural = (string) ($args[1] ?? '');
        $number = (int) ($args[2] ?? 1);

        return $number === 1 ? $single : $plural;
    }
}
