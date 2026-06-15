<?php

declare(strict_types=1);

return [
    'mapping' => [
        '/' => 'home',
        '/home' => 'page',
        '/about' => 'page-no-title',
        '/search' => 'search',
        '/search/*' => 'search',
        '/vi' => 'home',
        '/vi/trang-chu' => 'page',
        '/vi/ve-chung-toi' => 'page-no-title',
    ],
    'default' => 'home',
];
