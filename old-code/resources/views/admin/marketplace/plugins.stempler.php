<div class="plugin-browser">
    <div class="plugin-browser-header">
        <div class="plugin-tabs">
            <a href="?tab=featured" class="plugin-tab {{ $tab === 'featured' ? 'active' : '' }}">Featured</a>
            <a href="?tab=popular" class="plugin-tab {{ $tab === 'popular' ? 'active' : '' }}">Popular</a>
            <a href="?tab=latest" class="plugin-tab {{ $tab === 'latest' ? 'active' : '' }}">Latest</a>
        </div>
        <div class="plugin-search-bar">
            <input type="text" class="presto-input" placeholder="Search plugins..." style="min-width: 300px;">
        </div>
    </div>

    <div class="plugin-grid">
        @foreach($plugins as $plugin)
        <div class="plugin-card">
            <div class="plugin-card-top">
                <div class="plugin-icon-wrap">
                    @php
                        $iconUrl = '';
                        if (!empty($plugin['icons'])) {
                            $iconUrl = $plugin['icons']['2x'] ?? $plugin['icons']['1x'] ?? $plugin['icons']['svg'] ?? '';
                        }
                        if (empty($iconUrl)) {
                            $iconUrl = 'https://s.w.org/plugins/geopattern-icon/' . ($plugin['slug'] ?? 'default') . '.svg';
                        }
                        $bgColor = substr(md5($plugin['name'] ?? ''), 0, 6);
                    @endphp
                    @if($iconUrl)
                    <img src="{{ $iconUrl }}" alt="{{ $plugin['name'] }}">
                    @else
                    <img src="https://ui-avatars.com/api/?name={{ urlencode($plugin['name']) }}&background={{ $bgColor }}&color=fff" alt="{{ $plugin['name'] }}">
                    @endif
                </div>
                <div class="plugin-title-area">
                    <h3 class="plugin-title">{{ $plugin['name'] }}</h3>
                    <div class="plugin-author">By <a href="{{ $plugin['author']['url'] ?? '#' }}">{{ $plugin['author']['name'] }}</a></div>
                    <div class="plugin-rating">
                        @for($i = 0; $i < 5; $i++)
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="{{ $i < floor($plugin['stats']['rating'] / 20) ? '#fbbf24' : 'rgba(255,255,255,0.1)' }}"><path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg>
                        @endfor
                        <span style="font-size: 0.75rem; margin-left: 4px; color: var(--text-muted);">({{ $plugin['stats']['rating'] }})</span>
                    </div>
                </div>
                <div class="plugin-btn-group">
                    <button class="btn-install">Install Now</button>
                    <a href="#" class="btn-details">More Details</a>
                </div>
            </div>
            <div class="plugin-desc">
                {{ $plugin['description'] }}
            </div>
            <div class="plugin-card-footer">
                <div class="plugin-installs">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4m4-5 5 5 5-5m-5 5V3"/></svg>
                    {{ number_format($plugin['stats']['installs']) }}+ Active Installations
                </div>
                <div class="plugin-updated">
                    Last Updated: {{ $plugin['last_updated'] ?? 'Recent' }}
                </div>
                <div class="plugin-compatibility compatible">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                    Compatible with your version of PrestoWorld
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
        $range = 2;
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
