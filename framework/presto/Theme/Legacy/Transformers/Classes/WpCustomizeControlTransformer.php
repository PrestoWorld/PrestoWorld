<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\ClassicTheme\Transformers\Classes;

use PrestoWorld\Modules\ClassicTheme\Transformers\ClassTransformer;

class WpCustomizeControlTransformer extends ClassTransformer
{
    public function handles(): string
    {
        return 'WP_Customize_Control';
    }

    public function define(): void
    {
        if (class_exists('WP_Customize_Control', false)) {
            return;
        }

        eval('class WP_Customize_Control {}');
    }
}
