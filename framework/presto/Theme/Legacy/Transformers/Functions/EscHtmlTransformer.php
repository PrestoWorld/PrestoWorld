<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\ClassicTheme\Transformers\Functions;

use PrestoWorld\Modules\ClassicTheme\Transformers\FunctionTransformer;

class EscHtmlTransformer extends FunctionTransformer
{
    public function handles(): string
    {
        return 'esc_html';
    }

    public function handle(mixed ...$args): mixed
    {
        $text = (string) ($args[0] ?? '');
        return htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
