<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\ClassicTheme\Transformers\Functions;

use PrestoWorld\Modules\ClassicTheme\Transformers\FunctionTransformer;

class WpListPagesTransformer extends FunctionTransformer
{
    public function handles(): string
    {
        return 'wp_list_pages';
    }

    public function handle(mixed ...$args): mixed
    {
        $listArgs = $args[0] ?? [];

        if (!empty($listArgs['title_li'])) {
            echo '<li class="pagenav">' . $listArgs['title_li'] . '<ul>';
        }

        echo '<li><a href="/">Home</a></li>';

        if (!empty($listArgs['title_li'])) {
            echo '</ul></li>';
        }

        return null;
    }
}
