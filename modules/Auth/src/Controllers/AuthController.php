<?php

declare(strict_types=1);

namespace Modules\Auth\Controllers;

use App\Http\Controllers\AuthController as BaseAuthController;
use Witals\Framework\Http\Request;
use Witals\Framework\Http\Response;

class AuthController extends BaseAuthController
{
    /**
     * Show registration form (stub for now)
     */
    public function showRegister(Request $request): Response
    {
        $html = "<h1>Register (Coming Soon)</h1><p><a href='/login'>Back to Login</a></p>";
        return Response::html($html);
    }

    /**
     * Handle registration (stub for now)
     */
    public function handleRegister(Request $request): Response
    {
        return Response::json(['message' => 'Registration is not yet implemented'], 501);
    }
}
