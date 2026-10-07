<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\ClassicTheme\Transformers\Functions;

use PrestoWorld\Modules\ClassicTheme\Transformers\FunctionTransformer;

class GetTemplatePartTransformer extends FunctionTransformer
{
    public function handles(): string
    {
        return 'get_template_part';
    }

    public function handle(mixed ...$args): mixed
    {
        $slug = (string) ($args[0] ?? '');
        $name = isset($args[1]) ? (string) $args[1] : null;

        $themeDir = call_user_func('get_template_directory');

        $tentativePaths = [];
        if ($name !== null) {
            $tentativePaths[] = $themeDir . '/' . $slug . '-' . $name . '.php';
            $tentativePaths[] = $themeDir . '/template-parts/' . $slug . '-' . $name . '.php';
        }
        $tentativePaths[] = $themeDir . '/' . $slug . '.php';
        $tentativePaths[] = $themeDir . '/template-parts/' . $slug . '.php';

        foreach ($tentativePaths as $path) {
            if (file_exists($path)) {
                try {
                    (function () use ($path): void {
                        require $path;
                    })();
                } catch (\Throwable) {
                    // Template part may depend on WordPress functions not available
                }
                return null;
            }
        }

        return null;
    }
}
