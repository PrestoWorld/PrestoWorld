<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\ClassicTheme\Transformers\Functions;

use PrestoWorld\Modules\ClassicTheme\Transformers\FunctionTransformer;

class GetThePostThumbnailTransformer extends FunctionTransformer
{
    public function handles(): string
    {
        return 'get_the_post_thumbnail';
    }

    public function handle(mixed ...$args): mixed
    {
        return '';
    }
}
