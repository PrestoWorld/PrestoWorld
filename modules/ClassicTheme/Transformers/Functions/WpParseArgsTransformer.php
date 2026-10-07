<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\ClassicTheme\Transformers\Functions;

use PrestoWorld\Modules\ClassicTheme\Transformers\FunctionTransformer;

class WpParseArgsTransformer extends FunctionTransformer
{
    public function handles(): string
    {
        return 'wp_parse_args';
    }

    public function handle(mixed ...$args): mixed
    {
        $parsedArgs = $args[0] ?? [];
        $defaults = $args[1] ?? [];

        if (is_object($parsedArgs)) {
            $parsedArgs = get_object_vars($parsedArgs);
        }

        if (!is_array($parsedArgs)) {
            return $defaults;
        }

        return array_merge($defaults, $parsedArgs);
    }
}
