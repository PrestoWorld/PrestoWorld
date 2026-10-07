<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\ClassicTheme\Transformers\Functions;

use PrestoWorld\Modules\ClassicTheme\Transformers\FunctionTransformer;

class TheTitleTransformer extends FunctionTransformer
{
    public function handles(): string
    {
        return 'the_title';
    }

    public function handle(mixed ...$args): mixed
    {
        global $post;

        echo $post['post_title'] ?? $post->post_title ?? '';

        return null;
    }
}
