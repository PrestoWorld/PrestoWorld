<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\ComponentScannerService;
use Witals\Framework\Http\Response;

class SystemController
{
    private ComponentScannerService $scanner;

    public function __construct(ComponentScannerService $scanner)
    {
        $this->scanner = $scanner;
    }

    /**
     * Trigger a scan of plugins, themes and modules.
     * Use ?force=true to ignore cache.
     */
    public function scan(): Response
    {
        $force = isset($_GET['force']) && ($_GET['force'] === 'true' || $_GET['force'] === '1');
        
        try {
            $results = $this->scanner->scan($force);
            
            return Response::json([
                'status' => 'success',
                'message' => 'System components scanned successfully',
                'timestamp' => time(),
                'data' => $results
            ]);
        } catch (\Throwable $e) {
            return Response::json([
                'status' => 'error',
                'message' => 'Scan failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Clear the scan results cache.
     */
    public function clearCache(): Response
    {
        $cacheFile = storage_path('framework/cache/scan_results.php');
        
        if (file_exists($cacheFile)) {
            unlink($cacheFile);
            return Response::json([
                'status' => 'success',
                'message' => 'Component scan cache cleared.'
            ]);
        }

        return Response::json([
            'status' => 'info',
            'message' => 'No scan cache found to clear.'
        ]);
    }
}
