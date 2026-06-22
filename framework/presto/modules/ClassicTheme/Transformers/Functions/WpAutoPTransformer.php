<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\ClassicTheme\Transformers\Functions;

use PrestoWorld\Modules\ClassicTheme\Transformers\FunctionTransformer;

class WpAutoPTransformer extends FunctionTransformer
{
    public function handles(): string
    {
        return 'wpautop';
    }

    public function handle(mixed ...$args): mixed
    {
        $text = (string) ($args[0] ?? '');
        return '<p>' . str_replace("\n\n", "</p><p>", $text) . '</p>';
    }
}
