<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\ClassicTheme\Transformers\Functions;

use PrestoWorld\Modules\ClassicTheme\Transformers\FunctionTransformer;

class GetTheTitleTransformer extends FunctionTransformer
{
    public function handles(): string
    {
        return 'get_the_title';
    }

    public function handle(mixed ...$args): mixed
    {
        global $post;

        return $post['post_title'] ?? $post->post_title ?? '';
    }
}
