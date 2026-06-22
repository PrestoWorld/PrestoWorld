<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\ClassicTheme\Transformers\Functions;

use PrestoWorld\Modules\ClassicTheme\Transformers\FunctionTransformer;

class WpGetThemeTransformer extends FunctionTransformer
{
    public function handles(): string
    {
        return 'wp_get_theme';
    }

    public function handle(mixed ...$args): mixed
    {
        return new class {
            public function get(string $key): string
            {
                $versions = ['Version' => '1.0.0'];
                return $versions[$key] ?? '';
            }
        };
    }
}
