<?php

declare(strict_types=1);

use PrestoWorld\Modules\Search\PW_Query;

if (!function_exists('pw_query')) {
    /**
     * High-performance search query for PrestoWorld
     */
    function pw_query(array $args = []): PW_Query
    {
        $query = new PW_Query();
        $query->setEngine(app(Prestoworld\SearchEngine\SearchEngine::class));
        $query->query($args);
        return $query;
    }
}
