<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'PrestoWorld' }} - Next-Gen Framework</title>
    {!! app(\Witals\Framework\Support\AssetManager::class)->renderCss() !!}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #6366F1;
            --primary-glow: rgba(99, 102, 241, 0.2);
            --dark: #0F172A;
            --gray: #64748B;
            --light: #F8FAFC;
        }
        body { font-family: 'Plus Jakarta Sans', sans-serif; margin: 0; color: var(--dark); background: #fff; line-height: 1.5; }
        .container { max-width: 1200px; margin: 0 auto; padding: 0 24px; }
        
        header { background: rgba(255, 255, 255, 0.8); backdrop-filter: blur(20px); position: sticky; top: 0; z-index: 9999; border-bottom: 1px solid #f1f5f9; }
        .header-inner { height: 72px; display: flex; align-items: center; justify-content: space-between; }
        .logo { font-size: 22px; font-weight: 800; display: flex; align-items: center; gap: 10px; color: var(--dark); text-decoration: none; letter-spacing: -0.03em; }
        .logo span { color: var(--primary); }
        
        nav ul { display: flex; list-style: none; gap: 32px; margin: 0; padding: 0; }
        nav a { text-decoration: none; color: var(--gray); font-weight: 600; font-size: 14px; transition: 0.2s; }
        nav a:hover { color: var(--primary); }
        
        .header-actions { display: flex; align-items: center; gap: 16px; }
        .btn-primary { background: var(--primary); color: white; padding: 10px 20px; border-radius: 10px; text-decoration: none; font-weight: 700; font-size: 14px; transition: 0.3s; box-shadow: 0 4px 12px var(--primary-glow); }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 8px 20px var(--primary-glow); }
        .btn-ghost { color: var(--gray); font-weight: 700; text-decoration: none; font-size: 14px; }
        
        .gradient-text { background: linear-gradient(135deg, #6366F1, #A855F7); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
    </style>
</head>
<body>
    <header>
        <div class="container header-inner">
            <a href="/" class="logo">
                <svg width="28" height="28" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <rect width="32" height="32" rx="8" fill="#6366F1"/>
                    <path d="M12 10H20C21.1046 10 22 10.8954 22 12V20C22 21.1046 21.1046 22 20 22H12C10.8954 22 10 21.1046 10 20V12C10 10.8954 10.8954 10 12 10Z" stroke="white" stroke-width="2"/>
                </svg>
                Presto<span>World.</span>
            </a>
            <nav>
                <ul>
                    <?php 
                    $nav_items = app('contexts')->resolve('header.nav');
                    foreach ($nav_items as $item): 
                    ?>
                        <li><a href="{{ $item['url'] }}">{{ $item['label'] }}</a></li>
                    <?php endforeach; ?>
                </ul>
            </nav>
            <div class="header-actions">
                <a href="/dashboard" class="btn-ghost">Admin Console</a>
                <a href="/dashboard" class="btn-primary">Get Started</a>
            </div>
        </div>
    </header>
