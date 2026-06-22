<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\ClassicTheme\Transformers\Functions;

use PrestoWorld\Modules\ClassicTheme\Transformers\FunctionTransformer;

class GetTemplateDirectoryUriTransformer extends FunctionTransformer
{
    public function handles(): string
    {
        return 'get_template_directory_uri';
    }

    public function handle(mixed ...$args): mixed
    {
        $themeUrl = getenv('PW_THEME_URL')
            ?: '/wp-content/themes/' . basename(getenv('PW_THEME_DIR') ?: 'twentytwenty');
        return rtrim($themeUrl, '/');
    }
}
