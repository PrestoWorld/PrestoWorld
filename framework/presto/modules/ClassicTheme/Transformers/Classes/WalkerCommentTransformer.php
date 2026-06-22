<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\ClassicTheme\Transformers\Classes;

use PrestoWorld\Modules\ClassicTheme\Transformers\ClassTransformer;

class WalkerCommentTransformer extends ClassTransformer
{
    public function handles(): string
    {
        return 'Walker_Comment';
    }

    public function define(): void
    {
        if (class_exists('Walker_Comment', false)) {
            return;
        }

        if (!class_exists('Walker', false)) {
            (new WalkerTransformer())->define();
        }

        eval('class Walker_Comment extends Walker {}');
    }
}
