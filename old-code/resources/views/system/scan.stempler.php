<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Components Scan Results &mdash; PrestoWorld</title>
    <style>
        :root { --bg: #0f172a; --card: rgba(30, 41, 59, 0.7); --text: #f8fafc; --text-muted: #94a3b8; --accent: #6366f1; --highlight: #4ade80; --warning: #fbbf24; }
        body { background: var(--bg); color: var(--text); font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, sans-serif; margin: 0; padding: 2rem; }
        .container { max-width: 900px; margin: 0 auto; }
        .header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 2rem; }
        h1 { font-size: 1.8rem; font-weight: 700; margin: 0; }
        .scanned-at { color: var(--text-muted); font-size: 0.9rem; }
        .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; }
        .section { background: var(--card); backdrop-filter: blur(12px); border: 1px solid rgba(255,255,255,0.1); border-radius: 20px; padding: 1.5rem; }
        h2 { font-size: 1.25rem; font-weight: 600; margin-top: 0; margin-bottom: 1.25rem; border-bottom: 1px solid rgba(255,255,255,0.05); padding-bottom: 0.5rem; }
        .item-list { list-style: none; padding: 0; margin: 0; }
        .item { display: flex; align-items: center; justify-content: space-between; padding: 12px 0; border-bottom: 1px solid rgba(255,255,255,0.03); }
        .item:last-child { border-bottom: none; }
        .item-name { font-weight: 500; font-size: 0.95rem; }
        .badge { font-size: 0.75rem; font-weight: 700; padding: 4px 8px; border-radius: 6px; text-transform: uppercase; }
        .badge-wp { background: rgba(251, 191, 36, 0.1); color: var(--warning); border: 1px solid rgba(251, 191, 36, 0.2); }
        .badge-native { background: rgba(74, 222, 128, 0.1); color: var(--highlight); border: 1px solid rgba(74, 222, 128, 0.2); }
        .btn-refresh { background: var(--accent); color: white; text-decoration: none; padding: 10px 20px; border-radius: 12px; font-weight: 600; font-size: 0.9rem; box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3); }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div>
                <h1>System Components Scan</h1>
                <div class="scanned-at">Last scanned: {{ $scanned_at }}</div>
            </div>
            <a href="?force=true" class="btn-refresh">🔄 Force Re-scan</a>
        </div>

        <div class="grid">
            <!-- Plugins -->
            <div class="section">
                <h2>Plugins ({{ count($plugins) }})</h2>
                <ul class="item-list">
                    @foreach($plugins as $plugin)
                        <li class="item">
                            <span class="item-name">{{ $plugin['name'] }}</span>
                            @if($plugin['is_wordpress'])
                                <span class="badge badge-wp">WordPress</span>
                            @else
                                <span class="badge badge-native">Native</span>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </div>

            <!-- Themes -->
            <div class="section">
                <h2>Themes ({{ count($themes) }})</h2>
                <ul class="item-list">
                    @foreach($themes as $theme)
                        <li class="item">
                            <span class="item-name">{{ $theme['name'] }}</span>
                            @if($theme['is_wordpress'])
                                <span class="badge badge-wp">WordPress</span>
                            @else
                                <span class="badge badge-native">Native</span>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>

        <!-- Modules -->
        <div class="section" style="margin-top: 2rem;">
            <h2>Core Modules ({{ count($modules) }})</h2>
            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem;">
                @foreach($modules as $module)
                    <div class="item" style="background: rgba(255,255,255,0.02); padding: 12px; border-radius: 12px; border: 1px solid rgba(255,255,255,0.05);">
                        <span class="item-name">{{ $module['name'] }}</span>
                        <span class="badge badge-native">System</span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</body>
</html>
