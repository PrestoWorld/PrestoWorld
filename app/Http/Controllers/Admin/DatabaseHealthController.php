<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use PrestoWorld\Foundation\Database\SchemaVerifier;
use Cycle\Database\DatabaseInterface;
use Witals\Framework\Http\Response;

class DatabaseHealthController
{
    public function __construct(
        protected DatabaseInterface $db,
    ) {}

    public function health(): Response
    {
        $prefix = getenv('PW_TABLE_PREFIX') ?: 'pw_';
        $storagePath = defined('PW_BASE_PATH') ? PW_BASE_PATH . '/storage' : getcwd() . '/storage';

        $verifier = new SchemaVerifier($this->db, $prefix, $storagePath);
        $result = $verifier->verify();

        return Response::json([
            'healthy' => $result['healthy'],
            'total' => $result['total'],
            'existing' => $result['existing'],
            'missing' => $result['missing'],
            'checked_at' => date('c', $result['timestamp']),
        ]);
    }

    public function tables(): Response
    {
        $prefix = getenv('PW_TABLE_PREFIX') ?: 'pw_';
        $storagePath = defined('PW_BASE_PATH') ? PW_BASE_PATH . '/storage' : getcwd() . '/storage';

        $verifier = new SchemaVerifier($this->db, $prefix, $storagePath);
        $result = $verifier->verify();

        return Response::json([
            'tables' => $result['tables'],
            'required' => $verifier->getRequiredTables(),
            'checked_at' => date('c', $result['timestamp']),
        ]);
    }
}
