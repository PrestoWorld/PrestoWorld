<div class="theme-browser">
    <div class="plugin-browser-header">
        <div class="plugin-tabs">
            <a href="?tab=featured" class="plugin-tab {{ $tab === 'featured' ? 'active' : '' }}">Featured</a>
            <a href="?tab=popular" class="plugin-tab {{ $tab === 'popular' ? 'active' : '' }}">Popular</a>
            <a href="?tab=latest" class="plugin-tab {{ $tab === 'latest' ? 'active' : '' }}">Latest</a>
        </div>
        <div class="plugin-search-bar">
            <input type="text" class="presto-input" placeholder="Search themes..." style="min-width: 300px;">
        </div>
    </div>

    <div class="theme-grid">
        @foreach($themes as $theme)
        <div class="theme-card">
            <div class="theme-screenshot">
                @php
                $screenshotUrl = '';
                if (!empty($theme['screenshot'])) {
                    $screenshotUrl = $theme['screenshot'];
                } elseif (!empty($theme['screenshot_url'])) {
                    $screenshotUrl = $theme['screenshot_url'];
                }
                $placeholderUrl = 'https://placehold.co/400x300/12151c/6366f1?text=' . urlencode($theme['name'] ?? 'Theme');
                @endphp
                <img src="{{ $screenshotUrl ?: $placeholderUrl }}" alt="{{ $theme['name'] ?? 'Theme' }}">
    
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

    @if(($pagination['total_pages'] ?? 1) > 1)
    <div class="pagination" style="display: flex; justify-content: center; align-items: center; gap: 0.25rem; margin-top: 2rem; flex-wrap: wrap;">
        @php
        $currentPage = $pagination['page'] ?? 1;
        $totalPages = $pagination['total_pages'] ?? 1;
        $range = 2; // Show 2 pages before and after current
        @endphp

        @if($currentPage > 1)
        <a href="?tab={{ $tab }}&page={{ $currentPage - 1 }}" class="presto-btn presto-btn-secondary" style="padding: 0.5rem 1rem;">&laquo; Prev</a>
        @endif

        @if($currentPage > $range + 1)
            <a href="?tab={{ $tab }}&page=1" class="presto-btn presto-btn-secondary" style="padding: 0.5rem 1rem;">1</a>
            @if($currentPage > $range + 2)
            <span style="padding: 0.5rem; color: var(--text-muted);">...</span>
            @endif
        @endif

        @for($i = max(1, $currentPage - $range); $i <= min($totalPages, $currentPage + $range); $i++)
            @if($i == $currentPage)
            <span class="presto-btn" style="background: var(--primary); color: white; cursor: default; padding: 0.5rem 1rem;">{{ $i }}</span>
            @else
            <a href="?tab={{ $tab }}&page={{ $i }}" class="presto-btn presto-btn-secondary" style="padding: 0.5rem 1rem;">{{ $i }}</a>
            @endif
        @endfor

        @if($currentPage < $totalPages - $range)
            @if($currentPage < $totalPages - $range - 1)
            <span style="padding: 0.5rem; color: var(--text-muted);">...</span>
            @endif
            <a href="?tab={{ $tab }}&page={{ $totalPages }}" class="presto-btn presto-btn-secondary" style="padding: 0.5rem 1rem;">{{ $totalPages }}</a>
        @endif

        @if($currentPage < $totalPages)
        <a href="?tab={{ $tab }}&page={{ $currentPage + 1 }}" class="presto-btn presto-btn-secondary" style="padding: 0.5rem 1rem;">Next &raquo;</a>
        @endif
    </div>
    @endif
</div>
