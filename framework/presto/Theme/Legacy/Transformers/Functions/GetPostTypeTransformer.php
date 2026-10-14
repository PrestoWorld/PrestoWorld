<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\ClassicTheme\Transformers\Functions;

use PrestoWorld\Modules\ClassicTheme\Transformers\FunctionTransformer;

class GetPostTypeTransformer extends FunctionTransformer
{
    public function handles(): string
    {
        return 'get_post_type';
    }

    public function handle(mixed ...$args): mixed
    {
        global $post;

        return $post['post_type'] ?? $post->post_type ?? 'post';
    }
}
