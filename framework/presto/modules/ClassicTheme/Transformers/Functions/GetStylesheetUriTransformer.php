<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\ClassicTheme\Transformers\Functions;

use PrestoWorld\Modules\ClassicTheme\Transformers\FunctionTransformer;

class GetStylesheetUriTransformer extends FunctionTransformer
{
    public function handles(): string
    {
        return 'get_stylesheet_uri';
    }

    public function handle(mixed ...$args): mixed
    {
        return call_user_func('get_template_directory_uri') . '/style.css';
    }
}
