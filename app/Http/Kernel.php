<?php

declare(strict_types=1);

namespace App\Http;

use Witals\Framework\Contracts\Http\Kernel as KernelContract;
use Witals\Framework\Http\Request;
use Witals\Framework\Http\Response;

/**
 * HTTP Kernel
 * 
 * Handles incoming HTTP requests and dispatches them to the application.
 */
class Kernel implements KernelContract
{
    /**
     * Handle an incoming HTTP request.
     */
    public function handle(Request $request): Response
    {
        return Response::json([
            'message' => 'PrestoWorld DigitalCore is running!',
            'runtime' => $this->detectRuntime(),
            'status' => 'success'
        ]);
    }

    /**
     * Helper to show current runtime in response
     */
    protected function detectRuntime(): string
    {
        if (isset($_SERVER['RR_MODE'])) return 'RoadRunner';
        if (isset($_SERVER['FRANKENPHP_WORKER'])) return 'FrankenPHP';
        return 'Traditional (FPM/Litespeed)';
    }
}
