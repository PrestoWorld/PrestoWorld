<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\ClassicTheme\Transformers\Functions;

use PrestoWorld\Modules\ClassicTheme\Transformers\FunctionTransformer;

class GetOptionTransformer extends FunctionTransformer
{
    public function handles(): string
    {
        return 'get_option';
    }

    public function handle(mixed ...$args): mixed
    {
        $option = (string) ($args[0] ?? '');
        $default = $args[1] ?? false;

        $options = [
            'page_for_posts' => 0,
            'posts_per_page' => 10,
            'date_format' => 'F j, Y',
            'time_format' => 'g:i a',
            'blogname' => 'PrestoWorld',
            'blogdescription' => '',
            'siteurl' => '/',
            'home' => '/',
        ];

        return $options[$option] ?? $default;
    }
}
