<?php

declare(strict_types=1);

return [
    'locale' => getenv('APP_LOCALE') ?: 'en',
    'locales' => ['en', 'vi'],
    'debug' => (bool)(getenv('APP_DEBUG') ?: false),
    'env' => getenv('APP_ENV') ?: 'production',
];
