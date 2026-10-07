<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\ClassicTheme\Transformers\Functions;

use PrestoWorld\Modules\ClassicTheme\Transformers\FunctionTransformer;

class GetSearchFormTransformer extends FunctionTransformer
{
    public function handles(): string
    {
        return 'get_search_form';
    }

    public function handle(mixed ...$args): mixed
    {
        $options = $args[0] ?? [];
        $ariaLabel = is_array($options) ? ($options['aria_label'] ?? 'Search') : 'Search';

        echo '<form role="search" method="get" class="search-form" action="/">';
        echo '<label><span class="screen-reader-text">' . esc_html($ariaLabel) . '</span>';
        echo '<input type="search" class="search-field" name="s" /></label>';
        echo '</form>';

        return null;
    }
}
