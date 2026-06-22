<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\ClassicTheme\Transformers\Functions;

use PrestoWorld\Modules\ClassicTheme\Transformers\FunctionTransformer;

class GetThemeModTransformer extends FunctionTransformer
{
    public function handles(): string
    {
        return 'get_theme_mod';
    }

    public function handle(mixed ...$args): mixed
    {
        $name = (string) ($args[0] ?? '');
        $default = $args[1] ?? false;

        $mods = [
            'enable_header_search' => true,
            'retina_logo' => false,
        ];

        return $mods[$name] ?? $default;
    }
}
