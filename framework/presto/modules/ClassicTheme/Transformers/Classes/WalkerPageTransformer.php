<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\ClassicTheme\Transformers\Classes;

use PrestoWorld\Modules\ClassicTheme\Transformers\ClassTransformer;

class WalkerPageTransformer extends ClassTransformer
{
    public function handles(): string
    {
        return 'Walker_Page';
    }

    public function define(): void
    {
        if (class_exists('Walker_Page', false)) {
            return;
        }

        if (!class_exists('Walker', false)) {
            (new WalkerTransformer())->define();
        }

        eval('class Walker_Page extends Walker {}');
    }
}
