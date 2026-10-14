<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\ClassicTheme\Transformers\Functions;

use PrestoWorld\Modules\ClassicTheme\Transformers\FunctionTransformer;

class HavePostsTransformer extends FunctionTransformer
{
    public function handles(): string
    {
        return 'have_posts';
    }

    public function handle(mixed ...$args): mixed
    {
        global $wp_query;

        if (!isset($wp_query)) {
            $wp_query = new \stdClass();
            $wp_query->have_posts = false;
        }

        return $wp_query->have_posts ?? false;
    }
}
