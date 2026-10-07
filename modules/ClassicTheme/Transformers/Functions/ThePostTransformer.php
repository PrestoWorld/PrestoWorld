<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\ClassicTheme\Transformers\Functions;

use PrestoWorld\Modules\ClassicTheme\Transformers\FunctionTransformer;

class ThePostTransformer extends FunctionTransformer
{
    public function handles(): string
    {
        return 'the_post';
    }

    public function handle(mixed ...$args): mixed
    {
        global $post, $wp_query;

        if (isset($wp_query) && isset($wp_query->posts)) {
            $post = array_shift($wp_query->posts);
        }

        return null;
    }
}
