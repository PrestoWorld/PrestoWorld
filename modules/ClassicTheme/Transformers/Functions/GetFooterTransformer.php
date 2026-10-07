<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\ClassicTheme\Transformers\Functions;

use PrestoWorld\Modules\ClassicTheme\Transformers\FunctionTransformer;

class GetFooterTransformer extends FunctionTransformer
{
    public function handles(): string
    {
        return 'get_footer';
    }

    public function handle(mixed ...$args): mixed
    {
        $name = isset($args[0]) ? (string) $args[0] : null;
        call_user_func('get_template_part', 'footer', $name);
        return null;
    }
}
