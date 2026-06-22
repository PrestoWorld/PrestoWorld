<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\ClassicTheme\Transformers\Functions;

use PrestoWorld\Modules\ClassicTheme\Transformers\FunctionTransformer;

class WpNavMenuTransformer extends FunctionTransformer
{
    public function handles(): string
    {
        return 'wp_nav_menu';
    }

    public function handle(mixed ...$args): mixed
    {
        $menuArgs = $args[0] ?? [];

        if (is_array($menuArgs) && !empty($menuArgs['items_wrap'])) {
            echo str_replace('%3$s', '', $menuArgs['items_wrap']);
        }

        return null;
    }
}
