<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\ClassicTheme\Transformers\Functions;

use PrestoWorld\Modules\ClassicTheme\Transformers\FunctionTransformer;

class GetAvatarTransformer extends FunctionTransformer
{
    public function handles(): string
    {
        return 'get_avatar';
    }

    public function handle(mixed ...$args): mixed
    {
        $size = (int) ($args[1] ?? 96);
        $alt = (string) ($args[3] ?? '');

        $url = 'data:image/svg+xml,'
            . rawurlencode(
                '<svg xmlns="http://www.w3.org/2000/svg" width="'
                . $size . '" height="' . $size . '" fill="%23ccc">'
                . '<rect width="100%" height="100%"/>'
                . '<text x="50%" y="50%" text-anchor="middle" dy=".3em" fill="%23fff" font-size="'
                . ($size * 0.5) . '">?</text></svg>'
            );

        return '<img alt="' . call_user_func('esc_attr', $alt) . '" src="' . $url . '" class="avatar avatar-' . $size . '" height="' . $size . '" width="' . $size . '" />';
    }
}
