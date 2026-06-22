<?php

declare(strict_types=1);

return [
    'active' => getenv('PW_ACTIVE_THEME') ?: 'twentytwenty',
    'path' => getenv('PW_THEME_DIR') ?: null,
    'default_title' => 'PrestoWorld',
    'charset' => 'UTF-8',
    'viewport' => 'width=device-width, initial-scale=1.0',
    'css_reset' => '*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; } body { font-family: system-ui, sans-serif; line-height: 1.6; }',
];
