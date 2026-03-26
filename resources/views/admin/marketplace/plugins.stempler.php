<div class="plugin-browser">
    <div class="plugin-browser-header">
        <div class="plugin-tabs">
            <a href="?tab=featured" class="plugin-tab {{ $tab === 'featured' ? 'active' : '' }}">Featured</a>
            <a href="?tab=popular" class="plugin-tab {{ $tab === 'popular' ? 'active' : '' }}">Popular</a>
            <a href="?tab=recommended" class="plugin-tab {{ $tab === 'recommended' ? 'active' : '' }}">Recommended</a>
            <a href="?tab=favorites" class="plugin-tab {{ $tab === 'favorites' ? 'active' : '' }}">Favorites</a>
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
                    @if(!empty($plugin['icon']['svg']))
                    <img src="{{ $plugin['icon']['svg'] }}" alt="{{ $plugin['name'] }}">
                    @else
                    <img src="https://ui-avatars.com/api/?name={{ urlencode($plugin['name']) }}&background={{ str_replace('#', '', $plugin['icon']['color']) }}&color=fff" alt="{{ $plugin['name'] }}">
                    @endif
                </div>
                <div class="plugin-title-area">
                    <h3 class="plugin-title">{{ $plugin['name'] }}</h3>
                    <div class="plugin-author">By <a href="{{ $plugin['author']['url'] }}">{{ $plugin['author']['name'] }}</a></div>
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
</div>
