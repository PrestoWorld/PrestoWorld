<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\ClassicTheme\Transformers\Functions;

use PrestoWorld\Modules\ClassicTheme\Transformers\FunctionTransformer;

class WpGetAttachmentImageSrcTransformer extends FunctionTransformer
{
    public function handles(): string
    {
        return 'wp_get_attachment_image_src';
    }

    public function handle(mixed ...$args): mixed
    {
        return false;
    }
}
