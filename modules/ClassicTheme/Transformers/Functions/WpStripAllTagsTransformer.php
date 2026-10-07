<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\ClassicTheme\Transformers\Functions;

use PrestoWorld\Modules\ClassicTheme\Transformers\FunctionTransformer;

class WpStripAllTagsTransformer extends FunctionTransformer
{
    public function handles(): string
    {
        return 'wp_strip_all_tags';
    }

    public function handle(mixed ...$args): mixed
    {
        return strip_tags((string) ($args[0] ?? ''));
    }
}
