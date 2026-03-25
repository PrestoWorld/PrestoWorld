<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }} — DigitalCore Admin</title>
    {!! $assets_css !!}
    <style>{!! $inline_css !!}</style>
</head>
<body class="presto-admin">
    <div class="presto-admin-layout">
        <aside class="presto-sidebar">
            <div class="presto-sidebar-brand">
                <svg width="24" height="24" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <rect width="32" height="32" rx="8" fill="#6366f1"/>
                    <path d="M12 10H20C21.1046 10 22 10.8954 22 12V20C22 21.1046 21.1046 22 20 22H12C10.8954 22 10 21.1046 10 20V12C10 10.8954 10.8954 10 12 10Z" stroke="white" stroke-width="2"/>
                </svg>
                Digital<span>Core.</span>
            </div>
            
            {!! $nav_html !!}

            <div class="sidebar-footer">
                <div class="nav-user-profile">
                    <div class="avatar" style="background: linear-gradient(135deg, #6366f1, #a855f7);">AD</div>
                    <div class="info">
                        <span class="name">Alexander Dev</span>
                        <span class="role">Super Admin</span>
                    </div>
                    <button class="logout-btn">
                        <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </button>
                </div>
            </div>
        </aside>
        <main class="presto-main-wrapper">
            <header class="presto-main-header">
                <div class="header-breadcrumb">
                    {!! $breadcrumb_html !!}
                </div>
                <div class="header-actions">
                    <div class="header-search">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="18"><path d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        <input type="text" placeholder="Tìm kiếm License key, tên khách hàng...">
                    </div>
                    <div class="header-notif">
                        <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        <span class="pulse"></span>
                    </div>
                    {!! $add_btn !!}
                </div>
            </header>
            <div class="presto-content-area">
                <h1 class="page-title">{{ $title }}</h1>
                
                {!! $content !!}
                
                <footer class="presto-admin-footer">
                    <div class="footer-left">
                        <strong>DigitalCore</strong> v2.4.0 — Premium Admin Experience
                    </div>
                    <div class="footer-right">
                        Created with &hearts; by DeepMind & PrestoWorld Team
                    </div>
                </footer>
            </div>
        </main>
    </div>
    {!! $assets_js !!}
    <script><?php echo $inline_js; ?></script>
</body>
</html>
