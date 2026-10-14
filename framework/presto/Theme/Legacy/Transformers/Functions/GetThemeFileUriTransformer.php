<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\ClassicTheme\Transformers\Functions;

use PrestoWorld\Modules\ClassicTheme\Transformers\FunctionTransformer;

class GetThemeFileUriTransformer extends FunctionTransformer
{
    public function handles(): string
    {
        return 'get_theme_file_uri';
    }

    public function handle(mixed ...$args): mixed
    {
        $file = $args[0] ?? '';
        return call_user_func('get_template_directory_uri') . '/' . ltrim($file, '/');
    }
}
