<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\ClassicTheme\Transformers\Functions;

use PrestoWorld\Modules\ClassicTheme\Transformers\FunctionTransformer;

class WpGetThemeFilePathTransformer extends FunctionTransformer
{
    public function handles(): string
    {
        return 'wp_get_theme_file_path';
    }

    public function handle(mixed ...$args): mixed
    {
        return call_user_func('get_template_directory') . '/' . ltrim((string) ($args[0] ?? ''), '/');
    }
}
