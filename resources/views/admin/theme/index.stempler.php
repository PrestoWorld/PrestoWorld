<div class="admin-header-actions" style="margin-bottom: 2rem; display: flex; justify-content: space-between; align-items: baseline;">
    <h2 style="font-size: 1.8rem; font-weight: 800; letter-spacing: -0.04em;">Themes <span style="font-size: 0.9rem; font-weight: 400; color: var(--text-muted); vertical-align: middle; margin-left: 8px;">{{ count($themes) }}</span></h2>
    <div style="display: flex; gap: 1rem;">
        <a href="/admin/themes/install" class="presto-btn presto-btn-primary">+ Add New Theme</a>
        <input type="text" class="presto-input" placeholder="Search installed themes..." style="min-width: 300px;">
    </div>
</div>

<div class="theme-grid">
    @foreach($themes as $theme)
    <div class="theme-card {{ $theme->isActive() ? 'active-theme' : '' }}">
        @if($theme->isActive())
        <div class="theme-status-badge">Active</div>
        @endif
        
        <div class="theme-screenshot">
            <img src="{{ $theme->getScreenshot() }}" alt="{{ $theme->getTitle() }}">
            
            <div class="theme-actions">
                @if(!$theme->isActive())
                <button class="presto-btn presto-btn-primary" style="width: 100%;">Activate</button>
                <button class="presto-btn presto-btn-secondary" style="width: 100%;">Live Preview</button>
                @else
                <button class="presto-btn presto-btn-secondary" style="width: 100%;">Customize</button>
                @endif
            </div>
        </div>
        
        <div class="theme-info" style="{{ $theme->isActive() ? 'background: var(--primary);' : '' }}">
            <h3 class="theme-name" style="{{ $theme->isActive() ? 'color: white;' : '' }}">
                {{ $theme->getTitle() }}
            </h3>
            <div class="theme-details-link">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="{{ $theme->isActive() ? '#fff' : 'currentColor' }}" stroke-width="2"><path d="M12 21a9 9 0 1 1 0-18 9 9 0 0 1 0 18zm0-14v4m0 4h.01"/></svg>
            </div>
        </div>
    </div>
    @endforeach

    <!-- Add Theme Placeholder -->
    <a href="/admin/themes/install" class="theme-add-card">
        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 4v16m8-8H4"/></svg>
        <div style="font-weight: 700; font-size: 1.1rem;">Add Theme</div>
    </a>
</div>
