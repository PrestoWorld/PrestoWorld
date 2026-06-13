<?php

return [
    /*
    | Active skin for admin panel.
    | Options: 'presto-modern' (SSR), 'presto-spa' (CSR)
    */
    'skin' => env('ADMIN_SKIN', 'presto-spa'),

    /*
    | Authentication
    | Set enabled=false to bypass auth check during development.
    | In production, wire a real AuthContext provider.
    */
    'auth' => [
        'enabled' => env('ADMIN_AUTH_ENABLED', true),
    ],

    /*
    | Dashboard widget defaults
    */
    'dashboard' => [
        'columns' => 2,
        'max_widgets_per_column' => 10,
    ],
];
