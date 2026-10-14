<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\ClassicTheme\Transformers\Functions;

use PrestoWorld\Modules\ClassicTheme\Transformers\FunctionTransformer;

class GetBloginfoTransformer extends FunctionTransformer
{
    public function handles(): string
    {
        return 'get_bloginfo';
    }

    public function handle(mixed ...$args): mixed
    {
        $show = $args[0] ?? '';
        $filter = $args[1] ?? 'raw';

        $values = [
            'charset' => 'UTF-8',
            'name' => 'PrestoWorld',
            'description' => '',
            'url' => '/',
            'wpurl' => '/',
            'admin_email' => 'admin@example.com',
            'language' => 'en-US',
            'html_type' => 'text/html',
            'version' => '6.7',
            'stylesheet_url' => call_user_func('get_stylesheet_uri'),
            'stylesheet_directory' => call_user_func('get_stylesheet_directory'),
            'template_url' => call_user_func('get_template_directory_uri'),
            'template_directory' => call_user_func('get_template_directory_uri'),
        ];

        return $values[$show] ?? '';
    }
}
