<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\ClassicTheme\Transformers\Functions;

use PrestoWorld\Modules\ClassicTheme\Transformers\FunctionTransformer;

class EscHtmlETransformer extends FunctionTransformer
{
    public function handles(): string
    {
        return 'esc_html_e';
    }

    public function handle(mixed ...$args): mixed
    {
        echo htmlspecialchars((string) ($args[0] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        return null;
    }
}
