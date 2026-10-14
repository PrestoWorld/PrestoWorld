<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\ClassicTheme\Transformers\Classes;

use PrestoWorld\Modules\ClassicTheme\Transformers\ClassTransformer;

class WalkerTransformer extends ClassTransformer
{
    public function handles(): string
    {
        return 'Walker';
    }

    public function define(): void
    {
        if (class_exists('Walker', false)) {
            return;
        }

        eval('class Walker {}');
    }
}
