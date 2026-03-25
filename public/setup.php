<?php

/**
 * PrestoWorld Setup Utility
 * Handles initial environment configuration if .env is missing.
 */

declare(strict_types=1);

$envPath = __DIR__ . '/../.env';

// Safety check: Don't run setup if .env exists and is reasonably populated
if (file_exists($envPath) && filesize($envPath) > 50) {
    header('Location: ./');
    exit;
}

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dbConnection = $_POST['db_connection'] ?? 'mysql';
    $dbHost = $_POST['db_host'] ?? '127.0.0.1';
    $dbPort = $_POST['db_port'] ?? '3306';
    $dbDatabase = $_POST['db_database'] ?? 'prestoworld';
    $dbUsername = $_POST['db_username'] ?? 'root';
    $dbPassword = $_POST['db_password'] ?? '';
    
    $appName = $_POST['app_name'] ?? 'PrestoWorld';
    $appEnv = $_POST['app_env'] ?? 'local';
    $appDebug = isset($_POST['app_debug']) ? 'true' : 'false';
    
    $envContent = <<<EOT
APP_NAME="{$appName}"
APP_ENV={$appEnv}
APP_DEBUG={$appDebug}
APP_URL=http://localhost

DB_CONNECTION={$dbConnection}
DB_HOST={$dbHost}
DB_PORT={$dbPort}
DB_DATABASE="{$dbDatabase}"
DB_USERNAME="{$dbUsername}"
DB_PASSWORD="{$dbPassword}"

CACHE_DRIVER=file
SESSION_DRIVER=file
EOT;

    // Try to write .env
    if (@file_put_contents($envPath, $envContent)) {
        header('Location: ./');
        exit;
    } else {
        $error = "Failed to write .env file. Please check folder permissions.";
    }
}

// GUI Rendering
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PrestoWorld &mdash; Setup Your World</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #020617;
            --card: rgba(15, 23, 42, 0.6);
            --border: rgba(255, 255, 255, 0.1);
            --accent: #6366f1;
            --accent-glow: rgba(99, 102, 241, 0.3);
            --text: #f8fafc;
            --text-muted: #94a3b8;
            --input-bg: rgba(2, 6, 23, 0.4);
        }

        body {
            background: var(--bg);
            color: var(--text);
            font-family: 'Plus Jakarta Sans', sans-serif;
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            overflow-x: hidden;
        }

        .ambient-glow {
            position: absolute;
            width: 800px;
            height: 800px;
            background: radial-gradient(circle, var(--accent-glow) 0%, transparent 70%);
            z-index: -1;
            opacity: 0.4;
            pointer-events: none;
        }

        .container {
            width: 100%;
            max-width: 500px;
            padding: 20px;
            animation: fadeIn 0.6s ease-out;
        }

        .card {
            background: var(--card);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid var(--border);
            border-radius: 32px;
            padding: 3rem;
            box-shadow: 0 40px 100px -20px rgba(0, 0, 0, 0.8);
        }

        .logo {
            text-align: center;
            margin-bottom: 2.5rem;
        }

        .logo-text {
            font-size: 2rem;
            font-weight: 800;
            background: linear-gradient(135deg, #fff 0%, var(--accent) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            letter-spacing: -0.02em;
        }

        h2 {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            color: #fff;
        }

        .step-indicator {
            display: flex;
            gap: 12px;
            margin-bottom: 2rem;
        }

        .step {
            flex: 1;
            height: 4px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 99px;
            position: relative;
        }

        .step.active {
            background: var(--accent);
            box-shadow: 0 0 10px var(--accent-glow);
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        label {
            display: block;
            font-size: 0.875rem;
            font-weight: 500;
            margin-bottom: 0.5rem;
            color: var(--text-muted);
        }

        input, select {
            width: 100%;
            padding: 12px 16px;
            background: var(--input-bg);
            border: 1px solid var(--border);
            border-radius: 12px;
            color: #fff;
            font-family: inherit;
            font-size: 0.95rem;
            transition: all 0.2s;
            box-sizing: border-box;
        }

        input:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 4px var(--accent-glow);
        }

        .btn-row {
            margin-top: 2rem;
            display: flex;
            gap: 12px;
        }

        button {
            flex: 1;
            padding: 14px;
            border-radius: 14px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s;
            border: none;
            font-family: inherit;
        }

        .btn-primary {
            background: var(--accent);
            color: #fff;
            box-shadow: 0 4px 12px var(--accent-glow);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            background: #4f46e5;
            box-shadow: 0 8px 16px var(--accent-glow);
        }

        .btn-secondary {
            background: rgba(255, 255, 255, 0.05);
            color: var(--text);
        }

        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.1);
        }

        .error-msg {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.2);
            color: #fca5a5;
            padding: 1rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            font-size: 0.875rem;
        }

        .step-content {
            display: none;
        }

        .step-content.active {
            display: block;
            animation: slideIn 0.4s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes slideIn {
            from { opacity: 0; transform: translateX(10px); }
            to { opacity: 1; transform: translateX(0); }
        }
    </style>
</head>
<body>
    <div class="ambient-glow"></div>
    <div class="container">
        <div class="card">
            <div class="logo">
                <span class="logo-text">PrestoWorld</span>
            </div>

            <?php if (isset($error)): ?>
                <div class="error-msg"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <div class="step-indicator">
                <div class="step active" id="step1-ind"></div>
                <div class="step" id="step2-ind"></div>
                <div class="step" id="step3-ind"></div>
            </div>

            <form id="setupForm" method="POST">
                <!-- Step 1: Welcome -->
                <div class="step-content active" id="step1">
                    <h2>Welcome to the Future</h2>
                    <p style="color: var(--text-muted); line-height: 1.6; margin-bottom: 2rem;">
                        Ready to start your journey? Let's get your environment configured. PrestoWorld is built for Speed, Stability, and Scale.
                    </p>
                    <div class="form-group">
                        <label>Application Name</label>
                        <input type="text" name="app_name" value="PrestoWorld" placeholder="Enter your site name">
                    </div>
                    <div class="btn-row">
                        <button type="button" class="btn-primary" onclick="nextStep(2)">Get Started &rarr;</button>
                    </div>
                </div>

                <!-- Step 2: Database -->
                <div class="step-content" id="step2">
                    <h2>Database Connection</h2>
                    <div class="form-group">
                        <label>DB Connection</label>
                        <select name="db_connection">
                            <option value="mysql">MySQL / MariaDB</option>
                            <option value="postgres">PostgreSQL</option>
                            <option value="sqlite">SQLite</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Host</label>
                        <input type="text" name="db_host" value="127.0.0.1">
                    </div>
                    <div style="display: flex; gap: 15px;">
                        <div class="form-group" style="flex: 1;">
                            <label>Port</label>
                            <input type="text" name="db_port" value="3306">
                        </div>
                        <div class="form-group" style="flex: 2;">
                            <label>Database Name</label>
                            <input type="text" name="db_database" value="prestoworld">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Username</label>
                        <input type="text" name="db_username" value="root">
                    </div>
                    <div class="form-group">
                        <label>Password</label>
                        <input type="password" name="db_password" value="" placeholder="Omit if empty">
                    </div>
                    <div class="btn-row">
                        <button type="button" class="btn-secondary" onclick="nextStep(1)">&larr; Back</button>
                        <button type="button" class="btn-primary" onclick="nextStep(3)">Connect &rarr;</button>
                    </div>
                </div>

                <!-- Step 3: Optimization -->
                <div class="step-content" id="step3">
                    <h2>Almost There!</h2>
                    <div class="form-group">
                        <label> Environment</label>
                        <select name="app_env">
                            <option value="local">Development (Local)</option>
                            <option value="production">Production</option>
                        </select>
                    </div>
                    <div class="form-group" style="display: flex; align-items: center; gap: 12px; background: rgba(255,255,255,0.05); padding: 15px; border-radius: 12px;">
                        <input type="checkbox" name="app_debug" id="app_debug" checked style="width: auto;">
                        <label for="app_debug" style="margin: 0; cursor: pointer;">Enable Debug Mode</label>
                    </div>
                    <p style="font-size: 0.8rem; color: var(--text-muted); opacity: 0.7; margin-top: 1rem;">
                        Note: For better security, keep Debug Mode OFF in production.
                    </p>
                    <div class="btn-row">
                        <button type="button" class="btn-secondary" onclick="nextStep(2)">&larr; Back</button>
                        <button type="submit" class="btn-primary">Finalize Setup &check;</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script>
        function nextStep(step) {
            document.querySelectorAll('.step-content').forEach(el => el.classList.remove('active'));
            document.querySelectorAll('.step').forEach(el => el.classList.remove('active'));
            
            document.getElementById('step' + step).classList.add('active');
            
            // Activate indicators up to this step
            for(let i=1; i<=step; i++) {
                document.getElementById('step' + i + '-ind').classList.add('active');
            }
        }
    </script>
</body>
</html>
