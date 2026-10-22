<?php

declare(strict_types=1);

return [
    'table_prefix' => getenv('BOOKPRESS_TABLE_PREFIX') ?: (getenv('PW_TABLE_PREFIX') ?: 'pw_'),

    'headless' => [
        'business_id' => getenv('BOOKPRESS_HEADLESS_BUSINESS_ID') ?: '',
    ],

    'route_prefix' => getenv('BOOKPRESS_ROUTE_PREFIX') ?: '/bookpress',

    'domain' => [
        'suffix' => getenv('BOOKPRESS_DOMAIN_SUFFIX') ?: 'bookpress.vn',
        'custom_domain_allowed' => filter_var(getenv('BOOKPRESS_CUSTOM_DOMAIN_ALLOWED'), FILTER_VALIDATE_BOOLEAN) ?: false,
    ],

    'theme' => [
        'default' => getenv('BOOKPRESS_THEME_DEFAULT') ?: 'amber',
        'allowed' => ['amber', 'blue', 'emerald', 'rose', 'dark'],
    ],

    'ssl' => [
        'enabled' => filter_var(getenv('BOOKPRESS_SSL_ENABLED'), FILTER_VALIDATE_BOOLEAN) ?: true,
        'provider' => getenv('BOOKPRESS_SSL_PROVIDER') ?: 'letsencrypt',
    ],

    'store' => [
        'default_per_page' => (int) (getenv('BOOKPRESS_STORE_PER_PAGE') ?: 20),
        'max_per_page' => (int) (getenv('BOOKPRESS_STORE_MAX_PER_PAGE') ?: 100),
    ],
];
