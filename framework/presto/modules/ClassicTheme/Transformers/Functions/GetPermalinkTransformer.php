<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\ClassicTheme\Transformers\Functions;

use PrestoWorld\Modules\ClassicTheme\Transformers\FunctionTransformer;

class GetPermalinkTransformer extends FunctionTransformer
{
    public function handles(): string
    {
        return 'get_permalink';
    }

    public function handle(mixed ...$args): mixed
    {
        global $post;

        $slug = $post['slug'] ?? $post->post_slug ?? '';

        return '/' . ltrim($slug, '/');
    }
}
