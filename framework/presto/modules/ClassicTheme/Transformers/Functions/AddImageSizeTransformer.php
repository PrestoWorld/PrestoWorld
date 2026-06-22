<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\ClassicTheme\Transformers\Functions;

use PrestoWorld\Modules\ClassicTheme\Transformers\FunctionTransformer;

class AddImageSizeTransformer extends FunctionTransformer
{
    public function handles(): string
    {
        return 'add_image_size';
    }

    public function handle(mixed ...$args): mixed
    {
        return null;
    }
}
