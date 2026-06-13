<?php

return [
    'contexts' => [
        'frontend' => [
            'mode' => 'ssr',
            'roots' => [],
        ],

        'admin' => [
            'mode' => 'csr',
            'roots' => [],
        ],
    ],

    'register' => [
        'admin-core-css' => [
            'src' => 'css/admin-core.css',
            'type' => 'css',
            'version' => '1.0.0',
            'deps' => [],
            'context' => 'admin',
            'attributes' => ['media' => 'all'],
        ],

        'admin-dashboard-css' => [
            'src' => 'css/admin-dashboard.css',
            'type' => 'css',
            'version' => '1.0.0',
            'deps' => ['admin-core-css'],
            'context' => 'admin',
        ],

        'admin-spa-css' => [
            'src' => 'spa/css/admin-spa.css',
            'type' => 'css',
            'version' => '1.0.0',
            'deps' => [],
            'context' => 'admin',
        ],

        'admin-spa-js' => [
            'src' => 'spa/js/admin-spa.js',
            'type' => 'js',
            'version' => '1.0.0',
            'deps' => ['admin-spa-css'],
            'context' => 'admin',
            'attributes' => [
                'defer' => true,
                'type' => 'module',
            ],
        ],
    ],

    'transforms' => [
        [
            'from' => 'wp-content/uploads',
            'to' => 'storage/uploads',
            'patterns' => [
                '#/wp-content/uploads/(\d{4})/(\d{2})/#i' => '/storage/uploads/$1/$2/',
            ],
        ],
    ],
];
