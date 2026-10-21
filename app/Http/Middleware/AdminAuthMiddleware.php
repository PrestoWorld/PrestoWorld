<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Witals\Framework\Contracts\Auth\AuthContextInterface;
use Witals\Framework\Http\Request;
use Witals\Framework\Http\Response;

/**
 * Admin Authentication Middleware
 * 
 * Protects all /dashboard/* routes by verifying:
 * 1. User is authenticated (has valid token)
 * 2. User has admin role
 *
 * If not authenticated, redirects to login page (401 JSON for /api/*).
 * If authenticated but not admin, returns 403 Forbidden.
 *
 * Auth is ALWAYS enforced for the protected prefixes — there is no
 * development bypass anymore; use a real login to access admin surfaces.
 */
class AdminAuthMiddleware
{
    protected ?AuthContextInterface $auth;

    public function __construct(?AuthContextInterface $auth = null)
    {
        $this->auth = $auth;
    }

    public function handle(Request $request, callable $next): Response
    {
        $path = $request->path();

        // Only apply to admin surfaces: dashboard SPA, admin API and the
        // standalone Site Editor (Context Editor) page.
        $protectedPrefixes = ['/dashboard', '/api/admin', '/site-editor'];
        $isProtected = false;
        foreach ($protectedPrefixes as $prefix) {
            if (str_starts_with($path, $prefix)) {
                $isProtected = true;
                break;
            }
        }

        if (!$isProtected) {
            return $next($request);
        }

        if ($this->auth === null) {
            return $this->deny($request);
        }

        // Check if user is authenticated
        $token = $this->auth->getToken();
        if ($token === null) {
            return $this->deny($request);
        }

        // Check if user has admin role
        $actor = $this->auth->getActor();

        if (!$this->isAdmin($actor, $token)) {
            return $this->forbidden();
        }

        // User is authenticated and is admin — proceed
        return $next($request);
    }

    /**
     * Unauthenticated response: 401 JSON for /api/* (so fetch/XHR clients can
     * react without parsing HTML), 302 to login for page requests.
     */
    protected function deny(Request $request): Response
    {
        if (str_starts_with($request->path(), '/api/')) {
            return Response::html(
                json_encode(['error' => 'unauthenticated', 'message' => 'Authentication required.']),
                401,
                ['Content-Type' => 'application/json; charset=utf-8']
            );
        }

        return $this->redirectToLogin($request);
    }

    /**
     * Check if the actor has admin privileges
     */
    protected function isAdmin(?object $actor, ?\Witals\Framework\Contracts\Auth\TokenInterface $token = null): bool
    {
        // 1. Check actor if available
        if ($actor !== null) {
            if (method_exists($actor, 'isAdmin')) {
                return $actor->isAdmin();
            }
            if (method_exists($actor, 'hasRole')) {
                return $actor->hasRole('admin') || $actor->hasRole('super_admin') || $actor->hasRole('administrator');
            }
            if (property_exists($actor, 'role')) {
                return in_array($actor->role, ['admin', 'super_admin', 'administrator', 'editor'], true);
            }
            if ($actor instanceof \ArrayAccess && isset($actor['role'])) {
                return in_array($actor['role'], ['admin', 'super_admin', 'administrator', 'editor'], true);
            }
        }

        // 2. Check token payload (Fallback or Direct)
        if ($token !== null) {
            $payload = $token->getPayload();
            $role = $payload['role'] ?? $payload['roles'] ?? null;
            
            if (is_string($role)) {
                return in_array(strtolower($role), ['admin', 'super_admin', 'administrator', 'editor'], true);
            }
            if (is_array($role)) {
                $check = array_map('strtolower', $role);
                return !empty(array_intersect($check, ['admin', 'super_admin', 'administrator', 'editor']));
            }
        }

        return false;
    }

    /**
     * Redirect unauthenticated users to login page
     */
    protected function redirectToLogin(Request $request): Response
    {
        $currentUrl = $request->uri();
        $loginUrl = '/login?redirect=' . urlencode($currentUrl);

        return Response::html('', 302, ['Location' => $loginUrl]);
    }

    /**
     * Return 403 Forbidden for non-admin users
     */
    protected function forbidden(): Response
    {
        $html = <<<'HTML'
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>403 — Access Denied</title>
            <style>
                * { margin: 0; padding: 0; box-sizing: border-box; }
                body {
                    font-family: 'Plus Jakarta Sans', -apple-system, sans-serif;
                    background: #06080c;
                    color: #f1f5f9;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    min-height: 100vh;
                }
                .container {
                    text-align: center;
                    max-width: 480px;
                    padding: 60px 40px;
                    background: rgba(18, 22, 31, 0.85);
                    border-radius: 32px;
                    border: 1px solid rgba(255, 255, 255, 0.08);
                    backdrop-filter: blur(25px);
                }
                .code { font-size: 80px; font-weight: 800; color: #ef4444; opacity: 0.8; }
                h1 { font-size: 24px; font-weight: 800; margin: 16px 0 12px; }
                p { color: #94a3b8; line-height: 1.6; margin-bottom: 32px; }
                a {
                    display: inline-block;
                    padding: 14px 32px;
                    background: linear-gradient(135deg, #6366f1, #4f46e5);
                    color: #fff;
                    text-decoration: none;
                    border-radius: 18px;
                    font-weight: 800;
                    transition: 0.3s;
                }
                a:hover { transform: translateY(-3px); box-shadow: 0 10px 25px rgba(99, 102, 241, 0.35); }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="code">403</div>
                <h1>Truy cập bị từ chối</h1>
                <p>Bạn không có quyền truy cập khu vực quản trị. Vui lòng liên hệ quản trị viên nếu bạn cho rằng đây là lỗi.</p>
                <a href="/">← Về trang chủ</a>
            </div>
        </body>
        </html>
        HTML;

        return Response::html($html, 403);
    }
}
