<?php

namespace Plugins\NativeDemo;

use Prestoworld\Bridge\WordPress\Contracts\NativeComponentInterface;
use Witals\Framework\Http\Response;

/**
 * Class NativeDemoPlugin
 * 
 * Standard PrestoWorld Native Plugin.
 * Clean, fast, and optimized for RoadRunner.
 */
class NativeDemoPlugin implements NativeComponentInterface
{
    public function boot(): void
    {
        // Native hooks (fast path)
        add_filter('presto_system_status', function($status) {
            $status['native_plugin'] = 'Healthy';
            return $status;
        });
    }

    public function handle(string $action, array $params = []): Response
    {
        // Direct response, no echo, no buffering
        return Response::json([
            'status' => 'success',
            'message' => 'Native Presto Plugin handling request via RoadRunner',
            'timestamp' => time()
        ]);
    }
}

// Global registry for loader to find
return new NativeDemoPlugin();
