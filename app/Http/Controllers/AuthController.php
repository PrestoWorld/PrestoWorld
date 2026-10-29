<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Witals\Framework\Application;
use Witals\Framework\Http\Request;
use Witals\Framework\Http\Response;
use Cake\Chronos\Chronos;
use Psr\Log\LoggerInterface;
use Witals\Framework\Contracts\Auth\AuthContextInterface;
use Witals\Framework\Contracts\Auth\TokenStorageInterface;
use Witals\Framework\Contracts\Auth\HttpTransportInterface;
use Witals\Framework\Contracts\Auth\RateLimiterInterface;
use Witals\Framework\Contracts\Session\SessionInterface;

use Illuminate\Support\Str;

class AuthController
{
    protected Application $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Get site name from options
     */
    private function getSiteName(): string
    {
        try {
            $dbal = $this->app->make(\Cycle\Database\DatabaseProviderInterface::class);
            $db = $dbal->database();
            $prefix = env('WP_TABLE_PREFIX', 'wp_');
            
            $option = $db->table("{$prefix}options")
                ->where('option_name', 'blogname')
                ->run()
                ->fetch();
            
            if ($option && !empty($option['option_value'])) {
                return (string) $option['option_value'];
            }
        } catch (\Throwable $e) {
            // Ignore errors, use default
        }
        
        return 'PrestoWorld';
    }

    /**
     * Get site logo URL from theme mods
     */
    private function getSiteLogoUrl(): ?string
    {
        try {
            $dbal = $this->app->make(\Cycle\Database\DatabaseProviderInterface::class);
            $db = $dbal->database();
            $prefix = env('WP_TABLE_PREFIX', 'wp_');
            $theme = 'timeless'; // Default theme
            
            // Get theme mods
            $themeMods = $db->table("{$prefix}options")
                ->where('option_name', "theme_mods_{$theme}")
                ->run()
                ->fetch();
            
            if (!$themeMods || empty($themeMods['option_value'])) {
                return null;
            }
            
            $mods = @unserialize($themeMods['option_value']);
            if (!is_array($mods) || empty($mods['custom_logo'])) {
                return null;
            }
            
            $logoId = (int) $mods['custom_logo'];
            if ($logoId <= 0) {
                return null;
            }
            
            // Get attachment URL from wp_posts
            $attachment = $db->table("{$prefix}posts")
                ->where('ID', $logoId)
                ->where('post_type', 'attachment')
                ->run()
                ->fetch();
            
            if ($attachment && !empty($attachment['guid'])) {
                return (string) $attachment['guid'];
            }
        } catch (\Throwable $e) {
            // Ignore errors, return null
        }
        
        return null;
    }

    /**
     * Render brand HTML with logo or site name
     */
    private function renderBrand(string $siteName, ?string $logoUrl): string
    {
        if ($logoUrl) {
            $escapedUrl = htmlspecialchars($logoUrl, ENT_QUOTES, 'UTF-8');
            $escapedName = htmlspecialchars($siteName, ENT_QUOTES, 'UTF-8');
            return <<<HTML
                <img src="{$escapedUrl}" alt="{$escapedName}" style="height: 28px; width: auto; display: block;">
HTML;
        }
        
        $escapedName = htmlspecialchars($siteName, ENT_QUOTES, 'UTF-8');
        return <<<HTML
                <svg width="28" height="28" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg" style="flex-shrink: 0;">
                    <rect width="32" height="32" rx="8" fill="#6366f1"/>
                    <path d="M12 10H20C21.1046 10 22 10.8954 22 12V20C22 21.1046 21.1046 22 20 22H12C10.8954 22 10 21.1046 10 20V12C10 10.8954 10.8954 10 12 10Z" stroke="white" stroke-width="2"/>
                </svg>
                {$escapedName}
HTML;
    }

    /**
     * Show login form
     */
    public function showLogin(Request $request): Response
    {
        $sso = $request->query('sso');
        $returnTo = (string) $request->query('return_to', '');

        // If already authenticated
        $auth = $this->app->make(AuthContextInterface::class);
        if ($auth->getToken() !== null) {
            if ($sso === '1' && $returnTo !== '' && $this->isAllowedReturnTo($returnTo)) {
                $loginUrl = '/sso/init?return_to=' . urlencode($returnTo);
                return Response::html('', 302, ['Location' => $loginUrl]);
            }
            $redirect = $this->safeRedirect($request->query('redirect', '/dashboard'));
            return Response::html('', 302, ['Location' => $redirect]);
        }

        $redirect = $this->safeRedirect($request->query('redirect', '/dashboard'));
        $error = $request->query('error', '');
        $success = $request->query('success', '');
        
        // Get site name and logo
        $siteName = $this->getSiteName();
        $siteLogoUrl = $this->getSiteLogoUrl();
        
        $errorHtml = $error ? '<div class="alert-error">' . htmlspecialchars($error, ENT_QUOTES) . '</div>' : '';
        $successHtml = $success ? '<div class="alert-success">' . htmlspecialchars($success, ENT_QUOTES) . '</div>' : '';

        $ssoField = ($sso === '1' && $returnTo !== '') ? '<input type="hidden" name="sso" value="1"><input type="hidden" name="return_to" value="' . htmlspecialchars($returnTo, ENT_QUOTES) . '">' : '';

        $html = <<<HTML
        <!DOCTYPE html>
        <html lang="vi">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Đăng nhập — {$siteName} Admin</title>
            <link rel="preconnect" href="https://fonts.googleapis.com">
            <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
            <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
            <style>
                * { margin: 0; padding: 0; box-sizing: border-box; }
                body {
                    font-family: 'Plus Jakarta Sans', -apple-system, sans-serif;
                    background: #06080c;
                    color: #f1f5f9;
                    min-height: 100vh;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    -webkit-font-smoothing: antialiased;
                    background-image:
                        radial-gradient(circle at 20% 20%, rgba(99, 102, 241, 0.12) 0%, transparent 50%),
                        radial-gradient(circle at 80% 80%, rgba(168, 85, 247, 0.12) 0%, transparent 50%);
                }
                .login-container {
                    width: 100%;
                    max-width: 440px;
                    padding: 24px;
                }
                .login-card {
                    background: rgba(18, 22, 31, 0.85);
                    border-radius: 32px;
                    border: 1px solid rgba(255, 255, 255, 0.08);
                    padding: 48px 40px;
                    backdrop-filter: blur(25px);
                    box-shadow: 0 25px 60px rgba(0, 0, 0, 0.5);
                }
                .brand {
                    display: flex;
                    align-items: center;
                    gap: 12px;
                    font-size: 22px;
                    font-weight: 800;
                    margin-bottom: 40px;
                    letter-spacing: -0.04em;
                }
                .brand span { color: #6366f1; }
                .brand svg { flex-shrink: 0; }
                h1 {
                    font-size: 28px;
                    font-weight: 800;
                    margin-bottom: 8px;
                    letter-spacing: -0.03em;
                }
                .subtitle {
                    color: #64748b;
                    font-size: 14px;
                    margin-bottom: 36px;
                    line-height: 1.5;
                }
                .form-group {
                    margin-bottom: 24px;
                }
                label {
                    display: block;
                    font-size: 13px;
                    font-weight: 700;
                    color: #94a3b8;
                    margin-bottom: 10px;
                    text-transform: uppercase;
                    letter-spacing: 0.08em;
                }
                input[type="email"],
                input[type="password"],
                input[type="text"] {
                    width: 100%;
                    background: rgba(0, 0, 0, 0.3);
                    border: 1px solid rgba(255, 255, 255, 0.08);
                    border-radius: 16px;
                    padding: 16px 20px;
                    color: #f1f5f9;
                    font-size: 15px;
                    font-weight: 600;
                    outline: none;
                    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
                    font-family: inherit;
                }
                input:focus {
                    border-color: #6366f1;
                    box-shadow: 0 0 0 5px rgba(99, 102, 241, 0.25);
                    background: rgba(0, 0, 0, 0.45);
                }
                input::placeholder {
                    color: #475569;
                }
                .form-row {
                    display: flex;
                    align-items: center;
                    justify-content: space-between;
                    margin-bottom: 32px;
                }
                .remember {
                    display: flex;
                    align-items: center;
                    gap: 10px;
                    font-size: 14px;
                    color: #94a3b8;
                    cursor: pointer;
                    font-weight: 600;
                }
                .remember input[type="checkbox"] {
                    width: 18px;
                    height: 18px;
                    border-radius: 6px;
                    background: rgba(255, 255, 255, 0.05);
                    border: 1px solid rgba(255, 255, 255, 0.12);
                    appearance: none;
                    cursor: pointer;
                    position: relative;
                    transition: 0.3s;
                }
                .remember input[type="checkbox"]:checked {
                    background: #6366f1;
                    border-color: #6366f1;
                }
                .remember input[type="checkbox"]:checked::after {
                    content: "✓";
                    position: absolute;
                    color: #fff;
                    font-size: 11px;
                    font-weight: 900;
                    left: 50%;
                    top: 50%;
                    transform: translate(-50%, -50%);
                }
                .forgot {
                    color: #6366f1;
                    text-decoration: none;
                    font-size: 14px;
                    font-weight: 700;
                    transition: 0.25s;
                }
                .forgot:hover { color: #818cf8; }
                .btn-login {
                    width: 100%;
                    padding: 16px;
                    background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
                    color: #fff;
                    border: none;
                    border-radius: 16px;
                    font-size: 16px;
                    font-weight: 800;
                    cursor: pointer;
                    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
                    font-family: inherit;
                    letter-spacing: -0.01em;
                    box-shadow: 0 10px 25px rgba(99, 102, 241, 0.35);
                }
                .btn-login:hover {
                    transform: translateY(-3px);
                    box-shadow: 0 15px 35px rgba(99, 102, 241, 0.45);
                    filter: brightness(1.1);
                }
                .btn-login:active {
                    transform: translateY(0);
                }
                .alert-error {
                    background: rgba(239, 68, 68, 0.12);
                    border: 1px solid rgba(239, 68, 68, 0.25);
                    color: #fca5a5;
                    padding: 14px 20px;
                    border-radius: 14px;
                    font-size: 14px;
                    font-weight: 600;
                    margin-bottom: 24px;
                }
                .alert-success {
                    background: rgba(16, 185, 129, 0.12);
                    border: 1px solid rgba(16, 185, 129, 0.25);
                    color: #6ee7b7;
                    padding: 14px 20px;
                    border-radius: 14px;
                    font-size: 14px;
                    font-weight: 600;
                    margin-bottom: 24px;
                }
                .footer-text {
                    text-align: center;
                    margin-top: 32px;
                    font-size: 14px;
                    color: #64748b;
                }
                .footer-text a {
                    color: #6366f1;
                    text-decoration: none;
                    font-weight: 700;
                }
                .footer-text a:hover { color: #818cf8; }
            </style>
        </head>
        <body>
            <div class="login-container">
                <div class="login-card">
                    <div class="brand">
                        {$this->renderBrand($siteName, $siteLogoUrl)}
                    </div>

                    <h1>Đăng nhập</h1>
                    <p class="subtitle">Truy cập bảng điều khiển quản trị của bạn</p>

                    {$successHtml}
                    {$errorHtml}

                    <form method="POST" action="/login">
                        <input type="hidden" name="redirect" value="{$redirect}">
                        <input type="hidden" name="_token" value="{$this->csrfToken()}">
                        {$ssoField}

                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" id="email" name="email" placeholder="admin@example.com" required autofocus>
                        </div>

                        <div class="form-group">
                            <label for="password">Mật khẩu</label>
                            <input type="password" id="password" name="password" placeholder="••••••••" required>
                        </div>

                        <div class="form-row">
                            <label class="remember">
                                <input type="checkbox" name="remember" value="1">
                                Ghi nhớ đăng nhập
                            </label>
                            <a href="/forgot-password" class="forgot">Quên mật khẩu?</a>
                        </div>

                        <button type="submit" class="btn-login">Đăng nhập</button>
                    </form>
                </div>
                <p class="footer-text">
                    Chưa có tài khoản? <a href="/register">Đăng ký ngay</a>
                </p>
                <p class="footer-text">
                    <a href="/">← Về trang chủ</a>
                </p>
            </div>
        </body>
        </html>
        HTML;

        return Response::html($html);
    }

    /**
     * Handle login form submission
     */
    public function handleLogin(Request $request): Response
    {
        $email = $request->post('email', '');
        $password = $request->post('password', '');
        $redirect = $this->safeRedirect($request->post('redirect', '/dashboard'));
        $remember = (bool) $request->post('remember', false);
        $sso = $request->post('sso');
        $returnTo = (string) $request->post('return_to', '');

        $rateLimiter = $this->app->make(RateLimiterInterface::class);
        $rateLimitKey = 'login:' . strtolower($email);

        if (!$rateLimiter->attempt($rateLimitKey)) {
            $availableIn = $rateLimiter->availableIn($rateLimitKey);
            return Response::html('', 302, [
                'Location' => '/login?error=' . urlencode("Quá nhiều lần thử. Vui lòng thử lại sau {$availableIn} giây.") . '&redirect=' . urlencode($redirect)
            ]);
        }

        if (empty($email) || empty($password)) {
            return Response::html('', 302, [
                'Location' => '/login?error=' . urlencode('Vui lòng nhập email và mật khẩu') . '&redirect=' . urlencode($redirect)
            ]);
        }

        $token = $request->post('_token', '');
        $session = $this->app->make(SessionInterface::class);
        $expected = $session->get('_csrf_token', '');

        $this->app->make(LoggerInterface::class)->debug('Auth: CSRF check', [
            'received' => $token,
            'expected' => $expected,
            'session_id' => session_id(),
        ]);

        if ($token === '' || $expected === '' || !hash_equals($expected, $token)) {
            return Response::html('', 302, [
                'Location' => '/login?error=' . urlencode('Invalid or expired session token. Please try again.') . '&redirect=' . urlencode($redirect)
            ]);
        }

        // Authenticate user
        $user = $this->authenticateUser($email, $password);

        if ($user === null) {
            return Response::html('', 302, [
                'Location' => '/login?error=' . urlencode('Email hoặc mật khẩu không đúng') . '&redirect=' . urlencode($redirect)
            ]);
        }

        // Create auth token
        $tokenStorage = $this->app->make(TokenStorageInterface::class);
        $httpTransport = $this->app->make(HttpTransportInterface::class);
        $authContext = $this->app->make(AuthContextInterface::class);

        // Default token lifetime is 30 days so a session survives across
        // days of normal use (previously 8h, which silently dropped the
        // cookie and made the admin bar "disappear" while /dashboard stayed
        // open). "Remember me" also yields 30 days.
        $expiresAt = Chronos::now()->addDays(30);

        $token = $tokenStorage->create([
            'user_id' => $user['id'],
            'email'   => $user['email'],
            'role'    => $user['role'] ?? 'admin',
            'name'    => $user['name'] ?? $user['email'],
        ], $expiresAt);

        $authContext->start($token);

        // Create redirect response and set cookie
        if ($sso === '1' && $returnTo !== '' && $this->isAllowedReturnTo($returnTo)) {
            $response = Response::html('', 302, ['Location' => '/sso/init?return_to=' . urlencode($returnTo)]);
            return $httpTransport->commitToken($request, $response, $token, $expiresAt);
        }
        $response = Response::html('', 302, ['Location' => $redirect]);
        return $httpTransport->commitToken($request, $response, $token, $expiresAt);
    }

    /**
     * Handle logout
     */
    public function handleLogout(Request $request): Response
    {
        $authContext = $this->app->make(AuthContextInterface::class);
        $token = $authContext->getToken();

        if ($token !== null) {
            $tokenStorage = $this->app->make(TokenStorageInterface::class);
            $httpTransport = $this->app->make(HttpTransportInterface::class);

            $tokenStorage->delete($token);
            $authContext->close();

            $response = Response::html('', 302, ['Location' => '/login']);
            return $httpTransport->removeToken($request, $response, $token);
        }

        return Response::html('', 302, ['Location' => '/login']);
    }

    /**
     * Authenticate user against database
     */
    protected function authenticateUser(string $email, string $password): ?array
    {
        try {
            $dbal = $this->app->make(\Cycle\Database\DatabaseProviderInterface::class);
            $db = $dbal->database();
            
            // 1. Try native users table (honour PW_TABLE_PREFIX, e.g. pw_users)
            $prefix = getenv('PW_TABLE_PREFIX') ?: 'pw_';
            $usersTable = $prefix . 'users';

            if ($db->hasTable($usersTable)) {
                $user = $db->table($usersTable)
                    ->where('email', $email)
                    ->run()
                    ->fetch();

                // Column may be password_hash (schema) or password (legacy).
                $hash = is_array($user) ? ($user['password_hash'] ?? $user['password'] ?? null) : null;

                if ($user && is_string($hash) && $hash !== '' && password_verify($password, $hash)) {
                    return $user;
                }
            }

            // 2. Try WordPress users table (Bridge)
            $wpPrefix = env('WP_TABLE_PREFIX', 'wp_');
            $wpUsersTable = $db->hasTable('wp_users') ? 'wp_users' : ($db->hasTable($wpPrefix . 'users') ? $wpPrefix . 'users' : null);

            if ($wpUsersTable !== null) {
                $wpUser = $db->table($wpUsersTable)
                    ->where('user_email', $email)
                    ->orWhere('user_login', $email)
                    ->run()
                    ->fetch();
            } else {
                $wpUser = null;
            }

            if ($wpUser) {
                $hash = $wpUser['user_pass'];
                $isValid = false;

                // Handle standard bcrypt/argon2
                if (str_starts_with($hash, '$P$') || str_starts_with($hash, '$H$')) {
                    $isValid = $this->verifyPhpassHash($password, $hash);
                } elseif (strlen($hash) === 32) {
                    // Legacy MD5 — not secure. Prompt password reset instead.
                    // Instead of silently verifying with MD5, redirect to password reset.
                    throw new \RuntimeException('Mật khẩu của bạn sử dụng thuật toán cũ không còn an toàn. Vui lòng đặt lại mật khẩu.');
                } else {
                    $isValid = password_verify($password, $hash);
                }

                if ($isValid) {
                    // Fetch role from wp_usermeta (Bridge Role Detection)
                    $prefix = env('WP_TABLE_PREFIX', 'wp_');
                    $metaKey = "{$prefix}capabilities";
                    
                    $capabilitiesMeta = $db->table('wp_usermeta')
                        ->where('user_id', $wpUser['ID'])
                        ->where('meta_key', $metaKey)
                        ->run()
                        ->fetch();

                    $role = 'user'; // Default
                    if ($capabilitiesMeta && !empty($capabilitiesMeta['meta_value'])) {
                        // WordPress stores roles as a serialized array: a:1:{s:13:"administrator";b:1;}
                        $data = unserialize($capabilitiesMeta['meta_value'], ['allowed_classes' => false]);
                        if (is_array($data)) {
                            $roles = array_keys($data);
                            // Check if they have administrator role
                            if (in_array('administrator', $roles)) {
                                $role = 'administrator';
                            } elseif (in_array('editor', $roles)) {
                                $role = 'editor';
                            } else {
                                $role = reset($roles) ?: 'user';
                            }
                        }
                    }

                    return [
                        'id'    => $wpUser['ID'],
                        'email' => $wpUser['user_email'],
                        'name'  => $wpUser['display_name'],
                        'role'  => $role,
                    ];
                }
            }
        } catch (\Throwable $e) {
            $this->app->make(LoggerInterface::class)->error('Auth error: {message}', [
                'message' => $e->getMessage(),
                'exception' => $e,
            ]);
        }

        return null;
    }

    private function safeRedirect(string $url): string
    {
        if ($url === '' || str_starts_with($url, '/')) {
            if (str_starts_with($url, '//')) {
                return '/dashboard';
            }
            return $url;
        }

        return '/dashboard';
    }

    private function csrfToken(): string
    {
        $session = $this->app->make(SessionInterface::class);
        $token = $session->get('_csrf_token', '');

        if ($token === '') {
            $token = bin2hex(random_bytes(32));
            $session->set('_csrf_token', $token);
        }

        return $token;
    }

    private function verifyPhpassHash(string $password, string $hash): bool
    {

        $itoa64 = './0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';

        $countLog2 = strpos($itoa64, $hash[3]);
        if ($countLog2 < 7 || $countLog2 > 30) {
            return false;
        }

        $count = 1 << $countLog2;
        $salt = substr($hash, 4, 8);

        if (strlen($salt) !== 8) {
            return false;
        }

        $digest = md5($salt . $password, true);
        for ($i = 0; $i < $count; $i++) {
            $digest = md5($digest . $password, true);
        }

        $expected = substr($hash, 0, 12) . $this->phpassEncode64($digest, 16, $itoa64);

        return hash_equals($hash, $expected);
    }

    private function phpassEncode64(string $input, int $length, string $itoa64): string
    {
        $output = '';
        $i = 0;
        do {
            $value = ord($input[$i++]);
            $output .= $itoa64[$value & 0x3f];
            if ($i < $length) {
                $value |= ord($input[$i]) << 8;
            }
            $output .= $itoa64[($value >> 6) & 0x3f];
            if ($i++ >= $length) {
                break;
            }
            if ($i < $length) {
                $value |= ord($input[$i]) << 16;
            }
            $output .= $itoa64[($value >> 12) & 0x3f];
            if ($i++ >= $length) {
                break;
            }
            $output .= $itoa64[($value >> 18) & 0x3f];
        } while ($i < $length);

        return $output;
    }

    private function ssoSecret(): string
    {
        $key = env('APP_KEY') ?: env('SSO_SECRET') ?: 'bookowl-sso-secret-change-me';
        return hash('sha256', $key);
    }

    private function base64urlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private function base64urlDecode(string $data): string|false
    {
        $replaced = strtr($data, '-_', '+/');
        $padding = strlen($replaced) % 4;
        if ($padding > 0) {
            $replaced .= str_repeat('=', 4 - $padding);
        }
        return base64_decode($replaced);
    }

    private function signSso(array $payload): string
    {
        $data = json_encode($payload);
        if ($data === false) {
            $data = '{}';
        }
        $sig = hash_hmac('sha256', $data, $this->ssoSecret());
        return $this->base64urlEncode($data) . '.' . $sig;
    }

    private function verifySso(string $ticket): ?array
    {
        if (!str_contains($ticket, '.')) {
            return null;
        }
        [$dataB64, $sig] = explode('.', $ticket, 2);
        if ($dataB64 === '' || $sig === '') {
            return null;
        }
        $data = $this->base64urlDecode($dataB64);
        if ($data === false || $data === '') {
            return null;
        }
        $calc = hash_hmac('sha256', $data, $this->ssoSecret());
        if (!hash_equals($calc, $sig)) {
            return null;
        }
        $payload = json_decode($data, true);
        if (!is_array($payload)) {
            return null;
        }
        if (($payload['exp'] ?? 0) < time()) {
            return null;
        }
        if (($payload['nonce'] ?? '') === '') {
            return null;
        }
        if (empty($payload['user_id'])) {
            return null;
        }
        return $payload;
    }

    private function mainDomain(): string
    {
        $url = env('APP_URL') ?: env('BOOKOWL_MAIN_DOMAIN') ?: 'https://bookowl.org';
        $host = parse_url($url, PHP_URL_HOST);
        if (is_string($host) && $host !== '') {
            return $host;
        }
        if (is_string($url) && $url !== '') {
            $clean = preg_replace('#^https?://#i', '', $url);
            if (is_string($clean) && str_contains($clean, ':')) {
                $clean = explode(':', $clean)[0];
            }
            return $clean ?: 'bookowl.org';
        }
        return 'bookowl.org';
    }

    private function isAllowedReturnTo(string $returnTo): bool
    {
        if ($returnTo === '') {
            return false;
        }
        $parts = parse_url($returnTo);
        if (!is_array($parts)) {
            return false;
        }
        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        if ($scheme !== 'http' && $scheme !== 'https') {
            return false;
        }
        $host = strtolower((string) ($parts['host'] ?? ''));
        if ($host === '') {
            return false;
        }
        $main = strtolower($this->mainDomain());
        if ($host === $main) {
            return false; // don't callback to main domain
        }
        // allow localhost for dev
        if ($host === 'localhost') {
            return true;
        }
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            return true;
        }
        // try to allow known bookpress custom domains if possible
        try {
            $dbal = $this->app->make(\Cycle\Database\DatabaseProviderInterface::class);
            $db = $dbal->database();
            $row = $db->table('bookpress_store_domains')
                ->where('domain', $host)
                ->where('status', 'active')
                ->run()
                ->fetch();
            if ($row) {
                return true;
            }
        } catch (\Throwable $e) {
            // ignore
        }
        // also check subsite custom_domain
        try {
            $dbal = $this->app->make(\Cycle\Database\DatabaseProviderInterface::class);
            $db = $dbal->database();
            $row = $db->table('bookpress_subsites')
                ->where('custom_domain', $host)
                ->run()
                ->fetch();
            if ($row) {
                return true;
            }
        } catch (\Throwable $e) {
            // ignore
        }
        return false;
    }

    /**
     * SSO init - redirect to login or create ticket
     */
    public function ssoInit(Request $request): Response
    {
        $returnTo = (string) $request->query('return_to', '');
        if ($returnTo === '' || !$this->isAllowedReturnTo($returnTo)) {
            return Response::html('', 400);
        }

        $auth = $this->app->make(AuthContextInterface::class);
        $token = $auth->getToken();

        if ($token === null) {
            $loginUrl = '/login?sso=1&return_to=' . urlencode($returnTo);
            return Response::html('', 302, ['Location' => $loginUrl]);
        }

        $payload = $token->getPayload();
        if (!is_array($payload) || empty($payload['user_id'])) {
            $loginUrl = '/login?sso=1&return_to=' . urlencode($returnTo);
            return Response::html('', 302, ['Location' => $loginUrl]);
        }

        $ticketPayload = [
            'user_id' => $payload['user_id'],
            'email'   => $payload['email'] ?? null,
            'role'    => $payload['role'] ?? 'user',
            'name'    => $payload['name'] ?? ($payload['email'] ?? ''),
            'nonce'   => bin2hex(random_bytes(16)),
            'exp'     => time() + 60,
            'iat'     => time(),
        ];

        $ticket = $this->signSso($ticketPayload);
        $parts = parse_url($returnTo);
        $sep = isset($parts['query']) ? '&' : '?';
        $callbackUrl = $returnTo . $sep . 'sso_ticket=' . urlencode($ticket);
        return Response::html('', 302, ['Location' => $callbackUrl]);
    }

    /**
     * SSO callback - validate ticket and set cookie
     */
    public function ssoCallback(Request $request): Response
    {
        $ticket = (string) $request->query('sso_ticket', '');
        $returnTo = (string) $request->query('return_to', '');
        if ($returnTo === '' || !$this->isAllowedReturnTo($returnTo)) {
            // try to get from referer? but just fail
            return Response::html('', 400);
        }
        if ($ticket === '') {
            return Response::html('', 400);
        }

        $payload = $this->verifySso($ticket);
        if ($payload === null) {
            return Response::html('', 400);
        }

        $tokenStorage = $this->app->make(TokenStorageInterface::class);
        $httpTransport = $this->app->make(HttpTransportInterface::class);
        $authContext = $this->app->make(AuthContextInterface::class);

        $expiresAt = \Cake\Chronos\Chronos::now()->addDays(30);
        $token = $tokenStorage->create([
            'user_id' => $payload['user_id'],
            'email'   => $payload['email'] ?? '',
            'role'    => $payload['role'] ?? 'user',
            'name'    => $payload['name'] ?? '',
        ], $expiresAt);

        $authContext->start($token);
        $response = Response::html('', 302, ['Location' => $returnTo]);
        return $httpTransport->commitToken($request, $response, $token, $expiresAt);
    }
}
