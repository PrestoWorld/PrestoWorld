<?php

declare(strict_types=1);

return [
    'mapping' => [
        '/' => 'index',
        '/search' => 'search',
        '/search/*' => 'search',
    ],
    'default' => 'index',
];
