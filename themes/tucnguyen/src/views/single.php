<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php the_title(); ?> - PrestoWorld</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
            min-height: 100vh;
            padding: 40px 20px;
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 50px;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }
        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: #764ba2;
            text-decoration: none;
            font-weight: 500;
        }
        .back-link:hover {
            text-decoration: underline;
        }
        h1 {
            color: #764ba2;
            font-size: 2.5rem;
            margin-bottom: 20px;
            line-height: 1.2;
        }
        .meta {
            color: #888;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #eee;
        }
        .badge {
            display: inline-block;
            padding: 4px 12px;
            background: #764ba2;
            color: white;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
            margin-right: 10px;
        }
        .content {
            line-height: 1.8;
            color: #333;
            font-size: 1.1rem;
        }
        .content p {
            margin-bottom: 1.5rem;
        }
        .content h2 {
            color: #764ba2;
            margin-top: 2rem;
            margin-bottom: 1rem;
        }
        .content a {
            color: #764ba2;
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="<?php echo home_url('/'); ?>" class="back-link">← Back to Home</a>
        
        <h1><?php the_title(); ?></h1>
        
        <div class="meta">
            <span class="badge">POST</span>
            <span>ID: <?php echo get_the_ID(); ?></span>
        </div>
        
        <div class="content">
            <?php the_content(); ?>
        </div>
    </div>
</body>
</html>
