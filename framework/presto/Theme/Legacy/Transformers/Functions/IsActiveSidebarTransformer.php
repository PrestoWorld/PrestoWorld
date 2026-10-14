<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\ClassicTheme\Transformers\Functions;

use PrestoWorld\Modules\ClassicTheme\Transformers\FunctionTransformer;

class IsActiveSidebarTransformer extends FunctionTransformer
{
    public function handles(): string
    {
        return 'is_active_sidebar';
    }

    public function handle(mixed ...$args): mixed
    {
        return false;
    }
}
