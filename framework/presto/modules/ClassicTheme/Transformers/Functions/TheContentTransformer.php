<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\ClassicTheme\Transformers\Functions;

use PrestoWorld\Modules\ClassicTheme\Transformers\FunctionTransformer;

class TheContentTransformer extends FunctionTransformer
{
    public function handles(): string
    {
        return 'the_content';
    }

    public function handle(mixed ...$args): mixed
    {
        global $post;

        echo $post['post_content'] ?? $post->post_content ?? '';

        return null;
    }
}
