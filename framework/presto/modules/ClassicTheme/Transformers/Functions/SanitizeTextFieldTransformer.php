<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\ClassicTheme\Transformers\Functions;

use PrestoWorld\Modules\ClassicTheme\Transformers\FunctionTransformer;

class SanitizeTextFieldTransformer extends FunctionTransformer
{
    public function handles(): string
    {
        return 'sanitize_text_field';
    }

    public function handle(mixed ...$args): mixed
    {
        return trim(strip_tags((string) ($args[0] ?? '')));
    }
}
