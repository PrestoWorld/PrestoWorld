<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\ClassicTheme\Transformers\Functions;

use PrestoWorld\Modules\ClassicTheme\Transformers\FunctionTransformer;

class GetStylesheetDirectoryTransformer extends FunctionTransformer
{
    public function handles(): string
    {
        return 'get_stylesheet_directory';
    }

    public function handle(mixed ...$args): mixed
    {
        return call_user_func('get_template_directory');
    }
}
