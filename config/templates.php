<?php

declare(strict_types=1);

return [
    'mapping' => [
        '/' => 'index',
        '/home' => 'page',
        '/about' => 'page-no-title',
        '/search' => 'search',
        '/search/*' => 'search',
        '/vi' => 'index',
        '/vi/trang-chu' => 'page',
        '/vi/ve-chung-toi' => 'page-no-title',
    ],
    'default' => 'index',
];
