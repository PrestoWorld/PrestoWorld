<div class="installed-plugins">
    <!-- Header with search and filter -->
    <div class="plugin-browser-header">
        <div class="plugin-tabs">
            <a href="?filter=all" class="plugin-tab {{ ($_GET['filter'] ?? 'all') === 'all' ? 'active' : '' }}">All Plugins</a>
            <a href="?filter=active" class="plugin-tab {{ ($_GET['filter'] ?? '') === 'active' ? 'active' : '' }}">Active</a>
            <a href="?filter=inactive" class="plugin-tab {{ ($_GET['filter'] ?? '') === 'inactive' ? 'active' : '' }}">Inactive</a>
        </div>
        <div class="plugin-search-bar">
            <input type="text" class="presto-input" placeholder="Search installed plugins..." style="min-width: 300px;">
        </div>
    </div>

    @if(empty($plugins))
        <div class="presto-empty-state" style="text-align: center; padding: 60px;">
            <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="var(--text-muted)" stroke-width="1.5" style="margin-bottom: 20px;">
                <rect x="3" y="3" width="18" height="18" rx="2"/>
                <path d="M9 3v18"/>
                <path d="M15 3v18"/>
            </svg>
            <h3 style="color: var(--text-main); margin-bottom: 12px;">No Plugins Installed</h3>
            <p style="color: var(--text-muted); margin-bottom: 24px;">Start extending your application by installing plugins from the marketplace.</p>
            <a href="/dashboard/plugins/install" class="presto-btn presto-btn-primary">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 8px;">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                    <polyline points="17 8 12 3 7 8"/>
                    <line x1="12" y1="3" x2="12" y2="15"/>
                </svg>
                Browse Plugins
            </a>
        </div>
    @else
        <div class="plugin-grid">
            @foreach($plugins as $name => $plugin)
            @php
                $filter = $_GET['filter'] ?? 'all';
                $isActive = $plugin->isEnabled();
                if ($filter === 'active' && !$isActive) continue;
                if ($filter === 'inactive' && $isActive) continue;
            @endphp
            <div class="plugin-card {{ $isActive ? 'plugin-active' : 'plugin-inactive' }}">
                <!-- Status Badge -->
                <div class="plugin-status-badge {{ $isActive ? 'status-active' : 'status-inactive' }}">
                    {{ $isActive ? 'Active' : 'Inactive' }}
                </div>

                <div class="plugin-card-top">
                    <div class="plugin-icon-wrap">
                        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="var(--primary)" stroke-width="1.5">
                            <rect x="3" y="3" width="18" height="18" rx="2"/>
                            <path d="M9 3v18"/>
                            <path d="M15 3v18"/>
                        </svg>
                    </div>
                    <div class="plugin-title-area">
                        <h3 class="plugin-title">{{ $plugin->getName() }}</h3>
                        <div class="plugin-meta">
                            <span class="plugin-version">v{{ $plugin->getVersion() }}</span>
                            <span class="plugin-type">{{ ucfirst($plugin->getType()) }}</span>
                        </div>
                        @if(!empty($plugin->getDependencies()))
                        <div class="plugin-deps">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M12 2L2 7l10 5 10-5-10-5z"/>
                                <path d="M2 17l10 5 10-5"/>
                                <path d="M2 12l10 5 10-5"/>
                            </svg>
                            Requires: {{ implode(', ', $plugin->getDependencies()) }}
                        </div>
                        @endif
                    </div>
                </div>

                <div class="plugin-desc">
                    {{ $plugin->getDescription() ?: 'No description available.' }}
                </div>

                <div class="plugin-card-footer">
                    <div class="plugin-stats">
                        <div class="plugin-stat">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/>
                            </svg>
                            Priority: {{ $plugin->getPriority() }}
                        </div>
                        <div class="plugin-stat">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                            </svg>
                            {{ $isActive ? 'Loaded' : 'Not Loaded' }}
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="plugin-actions">
                    @if($isActive)
                        <button class="btn-action btn-deactivate" disabled title="Deactivate plugin">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"/>
                                <line x1="8" y1="12" x2="16" y2="12"/>
                            </svg>
                            Deactivate
                        </button>
                    @else
                        <button class="btn-action btn-activate" disabled title="Activate plugin">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polygon points="5 3 19 12 5 21 5 3"/>
                            </svg>
                            Activate
                        </button>
                    @endif

                    <button class="btn-action btn-settings" disabled title="Plugin settings">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="3"/>
                            <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/>
                        </svg>
                    </button>

                    <button class="btn-action btn-delete" disabled title="Delete plugin">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="3 6 5 6 21 6"/>
                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                        </svg>
                    </button>
                </div>
            </div>
            @endforeach
        </div>
    @endif
</div>

<style>
.installed-plugins {
    animation: fadeIn 0.6s ease-out;
}

/* Plugin Card Enhancements */
.plugin-card {
    position: relative;
    background: var(--bg-card);
    border: 1px solid var(--border);
    border-radius: 20px;
    padding: 1.5rem;
    transition: all 0.35s cubic-bezier(0.4, 0, 0.2, 1);
}

.plugin-card:hover {
    transform: translateY(-6px);
    border-color: var(--primary);
    box-shadow: 0 20px 40px -15px rgba(99, 102, 241, 0.2);
}

.plugin-card.plugin-active {
    border-left: 3px solid var(--success);
}

.plugin-card.plugin-inactive {
    border-left: 3px solid var(--warning);
    opacity: 0.85;
}

/* Status Badge */
.plugin-status-badge {
    position: absolute;
    top: 12px;
    right: 12px;
    padding: 4px 12px;
    border-radius: 99px;
    font-size: 0.7rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.plugin-status-badge.status-active {
    background: rgba(16, 185, 129, 0.15);
    color: var(--success);
}

.plugin-status-badge.status-inactive {
    background: rgba(245, 158, 11, 0.15);
    color: var(--warning);
}

/* Plugin Meta */
.plugin-meta {
    display: flex;
    gap: 12px;
    margin-top: 6px;
    font-size: 0.8rem;
}

.plugin-version {
    color: var(--primary);
    font-weight: 600;
    background: rgba(99, 102, 241, 0.1);
    padding: 2px 8px;
    border-radius: 6px;
}

.plugin-type {
    color: var(--text-muted);
    text-transform: capitalize;
}

.plugin-deps {
    margin-top: 8px;
    font-size: 0.75rem;
    color: var(--text-muted);
    display: flex;
    align-items: center;
    gap: 6px;
}

/* Plugin Stats */
.plugin-stats {
    display: flex;
    gap: 20px;
    font-size: 0.8rem;
    color: var(--text-muted);
}

.plugin-stat {
    display: flex;
    align-items: center;
    gap: 6px;
}

/* Plugin Actions */
.plugin-actions {
    display: flex;
    gap: 8px;
    margin-top: 1rem;
    padding-top: 1rem;
    border-top: 1px solid rgba(255, 255, 255, 0.06);
}

.btn-action {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    padding: 8px 16px;
    border-radius: 10px;
    font-size: 0.8rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    border: none;
    background: rgba(255, 255, 255, 0.05);
    color: var(--text-main);
}

.btn-action:hover:not(:disabled) {
    background: rgba(255, 255, 255, 0.1);
    transform: scale(1.05);
}

.btn-action:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.btn-activate {
    flex: 1;
    background: rgba(16, 185, 129, 0.15);
    color: var(--success);
}

.btn-activate:hover:not(:disabled) {
    background: rgba(16, 185, 129, 0.25);
}

.btn-deactivate {
    flex: 1;
    background: rgba(245, 158, 11, 0.15);
    color: var(--warning);
}

.btn-deactivate:hover:not(:disabled) {
    background: rgba(245, 158, 11, 0.25);
}

.btn-delete {
    color: var(--danger);
}

.btn-delete:hover:not(:disabled) {
    background: rgba(239, 68, 68, 0.15);
}

.btn-settings:hover:not(:disabled) {
    color: var(--primary);
}

/* Empty State Enhancement */
.presto-empty-state {
    background: var(--bg-card);
    border-radius: 24px;
    border: 1px dashed var(--border);
}

@@keyframes fadeIn {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>
