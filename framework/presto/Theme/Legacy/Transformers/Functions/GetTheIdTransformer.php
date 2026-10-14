<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\ClassicTheme\Transformers\Functions;

use PrestoWorld\Modules\ClassicTheme\Transformers\FunctionTransformer;

class GetTheIdTransformer extends FunctionTransformer
{
    public function handles(): string
    {
        return 'get_the_ID';
    }

    public function handle(mixed ...$args): mixed
    {
        global $post;

        return (int) ($post['ID'] ?? $post->ID ?? 0);
    }
}
