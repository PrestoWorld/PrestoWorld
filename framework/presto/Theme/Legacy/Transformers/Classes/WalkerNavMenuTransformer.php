<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\ClassicTheme\Transformers\Classes;

use PrestoWorld\Modules\ClassicTheme\Transformers\ClassTransformer;

class WalkerNavMenuTransformer extends ClassTransformer
{
    public function handles(): string
    {
        return 'Walker_Nav_Menu';
    }

    public function define(): void
    {
        if (class_exists('Walker_Nav_Menu', false)) {
            return;
        }

        if (!class_exists('Walker', false)) {
            (new WalkerTransformer())->define();
        }

        eval('class Walker_Nav_Menu extends Walker {}');
    }
}
