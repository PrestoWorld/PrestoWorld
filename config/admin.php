<?php

return [
    /*
    | Active skin for admin panel.
    | Options: 'presto-modern' (SSR), 'presto-spa' (CSR)
    */
    'skin' => env('ADMIN_SKIN', 'presto-spa'),

    /*
    | Dashboard widget defaults
    */
    'dashboard' => [
        'columns' => 2,
        'max_widgets_per_column' => 10,
    ],
];
