<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sample Page - PrestoWorld Native</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Outfit', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #0f172a;
            color: #f1f5f9;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .card {
            background: rgba(30, 41, 59, 0.7);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            padding: 60px;
            border-radius: 32px;
            max-width: 800px;
            width: 100%;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            text-align: center;
        }
        .badge {
            display: inline-block;
            padding: 6px 16px;
            background: linear-gradient(90deg, #38bdf8, #818cf8);
            color: #0f172a;
            font-weight: 700;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            border-radius: 100px;
            margin-bottom: 24px;
        }
        h1 {
            font-size: 3.5rem;
            font-weight: 800;
            margin-bottom: 24px;
            background: linear-gradient(to right, #fff, #94a3b8);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .content {
            font-size: 1.25rem;
            line-height: 1.7;
            color: #94a3b8;
            margin-bottom: 40px;
        }
        .btn {
            display: inline-block;
            padding: 16px 32px;
            background: #f1f5f9;
            color: #0f172a;
            text-decoration: none;
            font-weight: 600;
            border-radius: 12px;
            transition: all 0.2s;
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(255, 255, 255, 0.1);
            background: #fff;
        }
        .illustration {
            width: 120px;
            height: 120px;
            margin: 0 auto 30px;
            background: linear-gradient(135deg, rgba(56, 189, 248, 0.2), rgba(129, 140, 248, 0.2));
            border-radius: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="illustration">🧪</div>
        <div class="badge">Custom Template</div>
        <h1><?php the_title(); ?></h1>
        
        <div class="content">
            <p>This page is being rendered using a <strong>specific native template</strong>: <code>page-sample-page.php</code>.</p>
            <p>PrestoWorld's Native Engine supports the WordPress template hierarchy philosophy but with 10x the performance.</p>
        </div>

        <div style="background: rgba(0,0,0,0.2); padding: 20px; border-radius: 16px; margin-bottom: 30px; text-align: left; font-family: monospace; font-size: 0.9rem;">
            <?php the_content(); ?>
        </div>
        
        <a href="<?php echo home_url('/'); ?>" class="btn">Return to Dashboard</a>
    </div>
</body>
</html>
