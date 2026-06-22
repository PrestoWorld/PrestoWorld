<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\ClassicTheme\Transformers\Functions;

use PrestoWorld\Modules\ClassicTheme\Transformers\FunctionTransformer;

class WpEnqueueScriptTransformer extends FunctionTransformer
{
    public function handles(): string
    {
        return 'wp_enqueue_script';
    }

    public function handle(mixed ...$args): mixed
    {
        return null;
    }
}
