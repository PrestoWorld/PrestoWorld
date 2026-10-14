<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\ClassicTheme\Transformers\Functions;

use PrestoWorld\Modules\ClassicTheme\Transformers\FunctionTransformer;

class HasPostThumbnailTransformer extends FunctionTransformer
{
    public function handles(): string
    {
        return 'has_post_thumbnail';
    }

    public function handle(mixed ...$args): mixed
    {
        return false;
    }
}
