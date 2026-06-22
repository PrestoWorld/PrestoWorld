<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\ClassicTheme\Transformers\Functions;

use PrestoWorld\Modules\ClassicTheme\Transformers\FunctionTransformer;

class GetTemplateDirectoryTransformer extends FunctionTransformer
{
    public function handles(): string
    {
        return 'get_template_directory';
    }

    public function handle(mixed ...$args): mixed
    {
        $theme = getenv('PW_THEME_DIR') ?: '';
        return rtrim($theme, '/');
    }
}
