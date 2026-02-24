<?php

declare(strict_types=1);

namespace Modules\Auth\Controllers;

use App\Http\Controllers\AuthController as BaseAuthController;
use Witals\Framework\Http\Request;
use Witals\Framework\Http\Response;
use Cake\Chronos\Chronos;

class AuthController extends BaseAuthController
{
    /**
     * Show registration form
     */
    public function showRegister(Request $request): Response
    {
        $error = $request->query('error', '');
        $errorHtml = $error ? '<div class="alert-error">' . htmlspecialchars($error, ENT_QUOTES) . '</div>' : '';

        $html = <<<HTML
        <!DOCTYPE html>
        <html lang="vi">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Đăng ký thành viên — DigitalCore</title>
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
                    max-width: 480px;
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
                    margin-bottom: 20px;
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
                input {
                    width: 100%;
                    background: rgba(0, 0, 0, 0.3);
                    border: 1px solid rgba(255, 255, 255, 0.08);
                    border-radius: 16px;
                    padding: 14px 20px;
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
                    margin-top: 10px;
                }
                .btn-login:hover {
                    transform: translateY(-3px);
                    box-shadow: 0 15px 35px rgba(99, 102, 241, 0.45);
                    filter: brightness(1.1);
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
            </style>
        </head>
        <body>
            <div class="login-container">
                <div class="login-card">
                    <div class="brand">
                        <svg width="28" height="28" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <rect width="32" height="32" rx="8" fill="#6366f1"/>
                            <path d="M12 10H20C21.1046 10 22 10.8954 22 12V20C22 21.1046 21.1046 22 20 22H12C10.8954 22 10 21.1046 10 20V12C10 10.8954 10.8954 10 12 10Z" stroke="white" stroke-width="2"/>
                        </svg>
                        Digital<span>Core.</span>
                    </div>

                    <h1>Tạo tài khoản</h1>
                    <p class="subtitle">Tham gia cộng đồng quản trị DigitalCore</p>

                    {$errorHtml}

                    <form method="POST" action="/register">
                        <div class="form-group">
                            <label for="name">Họ và tên</label>
                            <input type="text" id="name" name="name" placeholder="Nguyễn Văn A" required autofocus>
                        </div>

                        <div class="form-group">
                            <label for="email">Địa chỉ Email</label>
                            <input type="email" id="email" name="email" placeholder="name@company.com" required>
                        </div>

                        <div class="form-group">
                            <label for="password">Mật khẩu</label>
                            <input type="password" id="password" name="password" placeholder="Tối thiểu 8 ký tự" required>
                        </div>

                        <div class="form-group">
                            <label for="password_confirmation">Xác nhận mật khẩu</label>
                            <input type="password" id="password_confirmation" name="password_confirmation" placeholder="••••••••" required>
                        </div>

                        <button type="submit" class="btn-login">Đăng ký tài khoản</button>
                    </form>
                </div>
                <p class="footer-text">
                    Đã có tài khoản? <a href="/login">Đăng nhập</a>
                </p>
            </div>
        </body>
        </html>
        HTML;

        return Response::html($html);
    }

    /**
     * Handle registration
     */
    public function handleRegister(Request $request): Response
    {
        $name = $request->post('name', '');
        $email = $request->post('email', '');
        $password = $request->post('password', '');
        $passwordConf = $request->post('password_confirmation', '');

        // Basic validation
        if (empty($name) || empty($email) || empty($password)) {
            return $this->redirectWithRegisterError('Vui lòng điền đầy đủ thông tin');
        }

        if ($password !== $passwordConf) {
            return $this->redirectWithRegisterError('Mật khẩu xác nhận không khớp');
        }

        if (strlen($password) < 8) {
            return $this->redirectWithRegisterError('Mật khẩu phải có ít nhất 8 ký tự');
        }

        try {
            $dbal = $this->app->make(\Cycle\Database\DatabaseProviderInterface::class);
            $db = $dbal->database();
            
            // Check if email exists
            $exists = $db->table('users')->where('email', $email)->count();
            if ($exists > 0) {
                return $this->redirectWithRegisterError('Email này đã được sử dụng');
            }

            // Create user
            $db->insert('users')->values([
                'name'     => $name,
                'email'    => $email,
                'password' => password_hash($password, PASSWORD_DEFAULT),
                'role'     => 'admin', // Default for now
                'created_at' => Chronos::now(),
                'updated_at' => Chronos::now(),
            ])->run();

            return Response::html('', 302, [
                'Location' => '/login?success=' . urlencode('Đăng ký thành công! Vui lòng đăng nhập.')
            ]);

        } catch (\Throwable $e) {
            error_log("Registration error: " . $e->getMessage());
            return $this->redirectWithRegisterError('Có lỗi xảy ra, vui lòng thử lại sau');
        }
    }

    protected function redirectWithRegisterError(string $message): Response
    {
        return Response::html('', 302, [
            'Location' => '/register?error=' . urlencode($message)
        ]);
    }
}
