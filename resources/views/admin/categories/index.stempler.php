<div class="categories-page">
    <div class="categories-header">
        <div class="header-actions">
            <a href="/dashboard/categories/create" class="presto-btn presto-btn-primary">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="12" y1="5" x2="12" y2="19"/>
                    <line x1="5" y1="12" x2="19" y2="12"/>
                </svg>
                Add New Category
            </a>
        </div>
    </div>

    <div class="presto-card">
        @if(empty($categories))
        <div class="empty-state">
            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="var(--text-muted)" stroke-width="1.5">
                <path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/>
            </svg>
            <p>No categories found</p>
            <a href="/dashboard/categories/create" class="presto-btn presto-btn-secondary">Create First Category</a>
        </div>
        @else
        <table class="presto-table categories-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Slug</th>
                    <th>Status</th>
                    <th>Sort Order</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($tree as $node)
                    @include('admin/categories/_category-row', ['node' => $node, 'level' => 0])
                @endforeach
            </tbody>
        </table>
        @endif
    </div>
</div>

<style>
.categories-page {
    animation: fadeIn 0.4s ease-out;
}

.categories-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
}

.categories-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
}

.categories-table th {
    text-align: left;
    padding: 16px;
    font-size: 0.75rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: var(--text-muted);
    border-bottom: 1px solid var(--border);
}

.categories-table td {
    padding: 16px;
    border-bottom: 1px solid var(--border);
    vertical-align: middle;
}

.categories-table tr:hover td {
    background: rgba(255, 255, 255, 0.02);
}

.category-name-cell {
    display: flex;
    align-items: center;
    gap: 12px;
}

.category-indent {
    width: 24px;
    height: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--text-muted);
}

.category-info {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.category-name {
    font-weight: 600;
    color: var(--text-main);
}

.category-description {
    font-size: 0.8rem;
    color: var(--text-muted);
}

.slug-code {
    font-family: 'SF Mono', monospace;
    font-size: 0.8rem;
    color: var(--text-dim);
    background: rgba(255, 255, 255, 0.03);
    padding: 4px 8px;
    border-radius: 6px;
}

.status-badge {
    padding: 6px 12px;
    border-radius: 99px;
    font-size: 0.75rem;
    font-weight: 600;
}

.status-active {
    background: rgba(16, 185, 129, 0.15);
    color: var(--success);
}

.status-inactive {
    background: rgba(100, 116, 139, 0.15);
    color: var(--text-muted);
}

.action-buttons {
    display: flex;
    gap: 8px;
}

.btn-action {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    border: none;
    background: rgba(255, 255, 255, 0.05);
    color: var(--text-dim);
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
    text-decoration: none;
}

.btn-action:hover {
    background: rgba(255, 255, 255, 0.1);
    color: var(--text-main);
}

.btn-edit:hover {
    color: var(--primary);
}

.btn-delete:hover {
    color: var(--danger);
}

.empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 16px;
    padding: 60px;
    color: var(--text-muted);
}

@@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>
