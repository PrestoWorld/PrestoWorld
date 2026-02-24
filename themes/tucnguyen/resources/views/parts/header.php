<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title ?? 'DigitalCore'; ?> - Elevate Your Digital Projects</title>
    <?php echo app(\Witals\Framework\Support\AssetManager::class)->renderCss(); ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #3B82F6;
            --secondary: #6366F1;
            --dark: #0F172A;
            --gray: #64748B;
            --light: #F8FAFC;
            --orange: #F59E0B;
            --cyan: #06B6D4;
        }
        body { font-family: 'Inter', sans-serif; margin: 0; color: var(--dark); background: #fff; }
        .container { max-width: 1200px; margin: 0 auto; padding: 0 20px; }
        
        header { background: rgba(255, 255, 255, 0.82); backdrop-filter: blur(10px); sticky; top: 0; z-index: 100; border-bottom: 1px solid #f1f5f9; }
        .header-inner { height: 80px; display: flex; align-items: center; justify-content: space-between; }
        .logo { font-size: 24px; font-weight: 800; display: flex; align-items: center; gap: 10px; color: var(--dark); text-decoration: none; }
        .logo span { color: var(--primary); }
        nav ul { display: flex; list-style: none; gap: 30px; margin: 0; padding: 0; }
        nav a { text-decoration: none; color: var(--gray); font-weight: 500; font-size: 15px; transition: 0.3s; }
        nav a:hover, nav a.active { color: var(--primary); }
        
        .header-actions { display: flex; align-items: center; gap: 20px; }
        .btn-login { text-decoration: none; color: var(--dark); font-weight: 600; font-size: 15px; }
        .btn-primary { background: var(--primary); color: white; padding: 10px 24px; border-radius: 8px; text-decoration: none; font-weight: 600; transition: 0.3s; }
        .btn-primary:hover { background: #2563EB; box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3); }

        .gradient-text { background: linear-gradient(90deg, #3B82F6, #6366F1); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        
        footer { background: #0b1120; color: #94a3b8; padding: 80px 0 30px; }
        .footer-grid { display: grid; grid-template-columns: 1.5fr 1fr 1fr 1.5fr; gap: 50px; border-bottom: 1px solid #1e293b; padding-bottom: 50px; }
        .footer-col h4 { color: white; margin-bottom: 25px; font-size: 18px; }
        .footer-col ul { list-style: none; padding: 0; margin: 0; }
        .footer-col ul li { margin-bottom: 15px; }
        .footer-col ul a { color: #94a3b8; text-decoration: none; transition: 0.3s; }
        .footer-col ul a:hover { color: var(--primary); }
        .newsletter { display: flex; gap: 10px; margin-top: 20px; }
        .newsletter input { background: #1e293b; border: none; padding: 12px 15px; border-radius: 6px; color: white; flex: 1; }
        .newsletter button { background: var(--primary); border: none; color: white; padding: 0 20px; border-radius: 6px; cursor: pointer; font-weight: 600; }
        .footer-bottom { display: flex; justify-content: space-between; padding-top: 30px; font-size: 14px; }
        .footer-bottom a { color: #94a3b8; text-decoration: none; }
        
        /* Blog Specific Styles */
        .blog-header { text-align: center; padding: 60px 0; }
        .blog-header h1 { font-size: 48px; font-weight: 800; margin-bottom: 15px; }
        .blog-header p { color: var(--gray); max-width: 600px; margin: 0 auto; line-height: 1.6; }
        
        .featured-card { display: grid; grid-template-columns: 1.2fr 1fr; gap: 40px; background: white; border-radius: 24px; overflow: hidden; box-shadow: 0 20px 40px rgba(0,0,0,0.05); margin-bottom: 60px; }
        .featured-img img { width: 100%; height: 100%; object-fit: cover; }
        .featured-body { padding: 40px; display: flex; flex-direction: column; justify-content: center; }
        .badge-featured { background: #FDE68A; color: #92400E; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 700; text-transform: uppercase; width: fit-content; margin-bottom: 20px; }
        .featured-body h2 { font-size: 32px; font-weight: 800; margin-bottom: 20px; line-height: 1.3; }
        .featured-body p { color: var(--gray); line-height: 1.7; margin-bottom: 30px; }
        
        .blog-grid-layout { display: grid; grid-template-columns: 3fr 1fr; gap: 40px; }
        .posts-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 30px; }
        .post-card { background: white; border-radius: 16px; overflow: hidden; border: 1px solid #f1f5f9; transition: 0.3s; }
        .post-card:hover { transform: translateY(-5px); box-shadow: 0 10px 20px rgba(0,0,0,0.05); }
        .post-thumb { height: 200px; position: relative; }
        .post-thumb img { width: 100%; height: 100%; object-fit: cover; }
        .post-category-badge { position: absolute; top: 15px; left: 15px; background: var(--primary); color: white; padding: 4px 10px; border-radius: 4px; font-size: 11px; font-weight: 700; text-transform: uppercase; }
        .post-info { padding: 25px; }
        .post-meta { font-size: 13px; color: var(--gray); margin-bottom: 12px; display: flex; gap: 15px; }
        .post-info h3 { font-size: 20px; margin-bottom: 15px; line-height: 1.4; }
        .post-info p { color: var(--gray); font-size: 14px; line-height: 1.6; margin-bottom: 20px; }
        
        .sidebar { position: sticky; top: 100px; height: fit-content; }
        .widget { background: white; border-radius: 16px; border: 1px solid #f1f5f9; padding: 25px; margin-bottom: 30px; }
        .widget-title { font-size: 18px; font-weight: 700; margin-bottom: 20px; padding-left: 10px; border-left: 4px solid var(--primary); }
        .search-widget { display: flex; gap: 10px; }
        .search-widget input { flex: 1; padding: 10px 15px; border-radius: 8px; border: 1px solid #e2e8f0; }
        .cat-list { list-style: none; padding: 0; margin: 0; }
        .cat-list li { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #f1f5f9; }
        .cat-list a { text-decoration: none; color: var(--gray); font-weight: 500; font-size: 14px; }
        .cat-count { background: #f1f5f9; padding: 2px 8px; border-radius: 10px; font-size: 12px; color: var(--gray); }
    </style>
</head>
<body>
    <header>
        <div class="container header-inner">
            <a href="/" class="logo">
                <svg width="32" height="32" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <rect width="32" height="32" rx="8" fill="#3B82F6"/>
                    <path d="M12 10H20C21.1046 10 22 10.8954 22 12V20C22 21.1046 21.1046 22 20 22H12C10.8954 22 10 21.1046 10 20V12C10 10.8954 10.8954 10 12 10Z" stroke="white" stroke-width="2"/>
                    <path d="M16 14V18" stroke="white" stroke-width="2" stroke-linecap="round"/>
                    <path d="M14 16H18" stroke="white" stroke-width="2" stroke-linecap="round"/>
                </svg>
                Digital<span>Core.</span>
            </a>
            <nav>
                <ul>
                    <li><a href="#">Marketplace</a></li>
                    <li><a href="/hosting">Hosting & VPS</a></li>
                    <li><a href="/blog" class="<?php echo str_contains($_SERVER['REQUEST_URI'], '/blog') ? 'active' : ''; ?>">Resources</a></li>
                    <li><a href="/memberships">Memberships</a></li>
                    <li><a href="/affiliates" class="<?php echo str_contains($_SERVER['REQUEST_URI'], '/affiliates') ? 'active' : ''; ?>">Partners</a></li>
                    <li><a href="/services">Services</a></li>
                </ul>
            </nav>
            <div class="header-actions">
                <a href="/auth/login" class="btn-login">Sign In</a>
                <a href="/auth/register" class="btn-primary">Get Started</a>
            </div>
        </div>
    </header>
