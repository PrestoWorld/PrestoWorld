<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\ClassicTheme\Transformers\Functions;

use PrestoWorld\Modules\ClassicTheme\Transformers\FunctionTransformer;

class EscAttrXTransformer extends FunctionTransformer
{
    public function handles(): string
    {
        return 'esc_attr_x';
    }

    public function handle(mixed ...$args): mixed
    {
        return htmlspecialchars((string) ($args[0] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
