<div class="theme-browser">
    <div class="plugin-browser-header">
        <div class="plugin-tabs">
            <a href="?tab=featured" class="plugin-tab {{ $tab === 'featured' ? 'active' : '' }}">Featured</a>
            <a href="?tab=popular" class="plugin-tab {{ $tab === 'popular' ? 'active' : '' }}">Popular</a>
            <a href="?tab=latest" class="plugin-tab {{ $tab === 'latest' ? 'active' : '' }}">Latest</a>
            <a href="?tab=favorites" class="plugin-tab {{ $tab === 'favorites' ? 'active' : '' }}">Favorites</a>
            <a href="?tab=filter" class="plugin-tab {{ $tab === 'filter' ? 'active' : '' }}">Feature Filter</a>
        </div>
        <div class="plugin-search-bar">
            <input type="text" class="presto-input" placeholder="Search themes..." style="min-width: 300px;">
        </div>
    </div>

    <div class="theme-grid">
        @foreach($themes as $theme)
        <div class="theme-card">
            <div class="theme-screenshot">
                <img src="{{ !empty($theme['icon']) ? $theme['icon'] : 'https://placehold.co/400x300/12151c/6366f1?text=' . urlencode($theme['name']) }}" alt="{{ $theme['name'] }}">
    
                <!-- Quick Actions Overlay -->
                <div class="theme-actions">
                    <button class="presto-btn presto-btn-primary" style="width: 100%;">Install Now</button>
                    <button class="presto-btn presto-btn-secondary" style="width: 100%;">Live Preview</button>
                </div>
                
                @if($theme['is_installed'] ?? false)
                <div class="theme-status-badge">Installed</div>
                @endif
            </div>
            <div class="theme-info">
                <h3 class="theme-name">{{ $theme['name'] }}</h3>
                <div class="theme-details-link">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 21a9 9 0 1 1 0-18 9 9 0 0 1 0 18zm0-14v4m0 4h.01"/></svg>
                </div>
            </div>
        </div>
        @endforeach
    </div>
</div>
