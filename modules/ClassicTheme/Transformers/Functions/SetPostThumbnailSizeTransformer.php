<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\ClassicTheme\Transformers\Functions;

use PrestoWorld\Modules\ClassicTheme\Transformers\FunctionTransformer;

class SetPostThumbnailSizeTransformer extends FunctionTransformer
{
    public function handles(): string
    {
        return 'set_post_thumbnail_size';
    }

    public function handle(mixed ...$args): mixed
    {
        return null;
    }
}
