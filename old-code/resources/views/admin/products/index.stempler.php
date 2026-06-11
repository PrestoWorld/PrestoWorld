<div class="products-page">
    <!-- Filter & Search Bar -->
    <div class="products-filter-bar">
        <form method="GET" action="/dashboard/products" class="filter-form">
            <div class="filter-tabs">
                <a href="?status=all{{ $search ? '&search=' . urlencode($search) : '' }}" 
                   class="filter-tab {{ $status === 'all' ? 'active' : '' }}">
                    All <span class="tab-count">{{ $total }}</span>
                </a>
                <a href="?status=active{{ $search ? '&search=' . urlencode($search) : '' }}" 
                   class="filter-tab {{ $status === 'active' ? 'active' : '' }}">
                    Active
                </a>
                <a href="?status=draft{{ $search ? '&search=' . urlencode($search) : '' }}" 
                   class="filter-tab {{ $status === 'draft' ? 'active' : '' }}">
                    Draft
                </a>
                <a href="?status=inactive{{ $search ? '&search=' . urlencode($search) : '' }}" 
                   class="filter-tab {{ $status === 'inactive' ? 'active' : '' }}">
                    Inactive
                </a>
            </div>
            
            <div class="search-box">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="11" cy="11" r="8"/>
                    <path d="m21 21-4.35-4.35"/>
                </svg>
                <input type="text" name="search" value="{{ $search }}" placeholder="Search products..." class="presto-input">
                @if($search)
                    <a href="?status={{ $status }}" class="clear-search">×</a>
                @endif
            </div>
        </form>
    </div>

    <!-- Products Table -->
    <div class="presto-card">
        <table class="presto-table products-table">
            <thead>
                <tr>
                    <th class="col-checkbox">
                        <input type="checkbox" id="select-all">
                    </th>
                    <th class="col-product">Product</th>
                    <th class="col-sku">SKU</th>
                    <th class="col-price">Price</th>
                    <th class="col-stock">Stock</th>
                    <th class="col-status">Status</th>
                    <th class="col-category">Category</th>
                    <th class="col-actions">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($products as $product)
                <tr>
                    <td class="col-checkbox">
                        <input type="checkbox" name="product_ids[]" value="{{ $product['id'] }}">
                    </td>
                    <td class="col-product">
                        <div class="product-info">
                            <div class="product-image">
                                @if($product['image'])
                                    <img src="{{ $product['image'] }}" alt="{{ $product['name'] }}">
                                @else
                                    <div class="image-placeholder">
                                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                            <rect x="3" y="3" width="18" height="18" rx="2"/>
                                            <circle cx="8.5" cy="8.5" r="1.5"/>
                                            <path d="M21 15l-5-5L5 21"/>
                                        </svg>
                                    </div>
                                @endif
                            </div>
                            <div class="product-details">
                                <div class="product-name">{{ $product['name'] }}</div>
                                <div class="product-date">Added {{ $product['created_at'] }}</div>
                            </div>
                        </div>
                    </td>
                    <td class="col-sku">
                        <code class="sku-code">{{ $product['sku'] }}</code>
                    </td>
                    <td class="col-price">
                        <div class="price-info">
                            @if($product['sale_price'])
                                <span class="sale-price">${{ number_format($product['sale_price'], 2) }}</span>
                                <span class="original-price">${{ number_format($product['price'], 2) }}</span>
                            @else
                                <span class="regular-price">${{ number_format($product['price'], 2) }}</span>
                            @endif
                        </div>
                    </td>
                    <td class="col-stock">
                        <span class="stock-badge {{ $product['stock'] > 0 ? 'in-stock' : 'out-of-stock' }}">
                            {{ $product['stock'] }} units
                        </span>
                    </td>
                    <td class="col-status">
                        <span class="status-badge status-{{ $product['status'] }}">
                            {{ ucfirst($product['status']) }}
                        </span>
                    </td>
                    <td class="col-category">
                        <span class="category-tag">{{ $product['category'] }}</span>
                    </td>
                    <td class="col-actions">
                        <div class="action-buttons">
                            <a href="/dashboard/products/edit/{{ $product['id'] }}" class="btn-action btn-edit" title="Edit">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                                </svg>
                            </a>
                            <button class="btn-action btn-delete" title="Delete" disabled>
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="3 6 5 6 21 6"/>
                                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/>
                                    <path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                                </svg>
                            </button>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="empty-cell">
                        <div class="empty-state">
                            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="var(--text-muted)" stroke-width="1.5">
                                <rect x="3" y="3" width="18" height="18" rx="2"/>
                                <path d="M9 3v18"/>
                                <path d="M15 3v18"/>
                            </svg>
                            <p>No products found</p>
                            @if($search)
                                <a href="?status={{ $status }}" class="presto-btn presto-btn-secondary">Clear Search</a>
                            @endif
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    @if($total > $perPage)
    <div class="pagination">
        @php
            $totalPages = ceil($total / $perPage);
            $start = max(1, $page - 2);
            $end = min($totalPages, $page + 2);
        @endphp
        
        @if($page > 1)
            <a href="?page={{ $page - 1 }}&status={{ $status }}{{ $search ? '&search=' . urlencode($search) : '' }}" class="page-link">← Prev</a>
        @endif
        
        @for($i = $start; $i <= $end; $i++)
            @if($i == $page)
                <span class="page-link active">{{ $i }}</span>
            @else
                <a href="?page={{ $i }}&status={{ $status }}{{ $search ? '&search=' . urlencode($search) : '' }}" class="page-link">{{ $i }}</a>
            @endif
        @endfor
        
        @if($page < $totalPages)
            <a href="?page={{ $page + 1 }}&status={{ $status }}{{ $search ? '&search=' . urlencode($search) : '' }}" class="page-link">Next →</a>
        @endif
        
        <span class="page-info">{{ $total }} total</span>
    </div>
    @endif
</div>

<style>
.products-page {
    animation: fadeIn 0.4s ease-out;
}

.products-filter-bar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
}

.filter-form {
    display: flex;
    gap: 20px;
    align-items: center;
    flex: 1;
}

.filter-tabs {
    display: flex;
    gap: 8px;
    background: var(--bg-card);
    padding: 6px;
    border-radius: 12px;
    border: 1px solid var(--border);
}

.filter-tab {
    padding: 8px 16px;
    border-radius: 8px;
    font-size: 0.85rem;
    font-weight: 600;
    color: var(--text-dim);
    text-decoration: none;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    gap: 6px;
}

.filter-tab:hover {
    color: var(--text-main);
    background: rgba(255, 255, 255, 0.03);
}

.filter-tab.active {
    background: var(--primary);
    color: white;
}

.tab-count {
    background: rgba(255, 255, 255, 0.2);
    padding: 2px 8px;
    border-radius: 99px;
    font-size: 0.7rem;
}

.search-box {
    position: relative;
    display: flex;
    align-items: center;
    max-width: 300px;
}

.search-box svg {
    position: absolute;
    left: 12px;
    color: var(--text-muted);
    pointer-events: none;
}

.search-box input {
    padding-left: 40px;
    padding-right: 30px;
}

.clear-search {
    position: absolute;
    right: 10px;
    color: var(--text-muted);
    text-decoration: none;
    font-size: 1.2rem;
    padding: 0 4px;
}

.clear-search:hover {
    color: var(--danger);
}

/* Table Styles */
.products-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
}

.products-table th {
    text-align: left;
    padding: 16px;
    font-size: 0.75rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: var(--text-muted);
    border-bottom: 1px solid var(--border);
}

.products-table td {
    padding: 16px;
    border-bottom: 1px solid var(--border);
    vertical-align: middle;
}

.products-table tr:hover td {
    background: rgba(255, 255, 255, 0.02);
}

/* Column specific */
.col-checkbox { width: 40px; }
.col-checkbox input {
    width: 18px;
    height: 18px;
    accent-color: var(--primary);
}

.col-product { min-width: 250px; }
.col-sku { width: 140px; }
.col-price { width: 120px; }
.col-stock { width: 100px; }
.col-status { width: 100px; }
.col-category { width: 120px; }
.col-actions { width: 100px; }

/* Product Info */
.product-info {
    display: flex;
    align-items: center;
    gap: 16px;
}

.product-image {
    width: 50px;
    height: 50px;
    border-radius: 10px;
    background: var(--bg-side);
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    flex-shrink: 0;
}

.product-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.image-placeholder {
    color: var(--text-muted);
}

.product-details {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.product-name {
    font-weight: 600;
    color: var(--text-main);
}

.product-date {
    font-size: 0.75rem;
    color: var(--text-muted);
}

/* SKU */
.sku-code {
    font-family: 'SF Mono', monospace;
    font-size: 0.8rem;
    color: var(--text-dim);
    background: rgba(255, 255, 255, 0.03);
    padding: 4px 8px;
    border-radius: 6px;
}

/* Price */
.price-info {
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.regular-price, .sale-price {
    font-weight: 700;
    color: var(--success);
}

.original-price {
    font-size: 0.8rem;
    color: var(--text-muted);
    text-decoration: line-through;
}

/* Stock Badge */
.stock-badge {
    padding: 4px 10px;
    border-radius: 99px;
    font-size: 0.75rem;
    font-weight: 600;
}

.stock-badge.in-stock {
    background: rgba(16, 185, 129, 0.15);
    color: var(--success);
}

.stock-badge.out-of-stock {
    background: rgba(239, 68, 68, 0.15);
    color: var(--danger);
}

/* Status Badge */
.status-badge {
    padding: 6px 12px;
    border-radius: 99px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: capitalize;
}

.status-active {
    background: rgba(16, 185, 129, 0.15);
    color: var(--success);
}

.status-draft {
    background: rgba(245, 158, 11, 0.15);
    color: var(--warning);
}

.status-inactive {
    background: rgba(100, 116, 139, 0.15);
    color: var(--text-muted);
}

/* Category Tag */
.category-tag {
    padding: 4px 12px;
    border-radius: 8px;
    font-size: 0.75rem;
    font-weight: 600;
    color: var(--primary);
    background: rgba(99, 102, 241, 0.1);
}

/* Action Buttons */
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
}

.btn-action:hover:not(:disabled) {
    background: rgba(255, 255, 255, 0.1);
    color: var(--text-main);
}

.btn-edit:hover {
    color: var(--primary);
}

.btn-delete:hover {
    color: var(--danger);
}

.btn-action:disabled {
    opacity: 0.4;
    cursor: not-allowed;
}

/* Empty State */
.empty-cell {
    padding: 60px !important;
    text-align: center;
}

.empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 16px;
    color: var(--text-muted);
}

.empty-state p {
    margin: 0;
}

/* Pagination */
.pagination {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    margin-top: 24px;
    padding: 16px;
}

.page-link {
    padding: 8px 16px;
    border-radius: 10px;
    font-size: 0.85rem;
    font-weight: 600;
    color: var(--text-dim);
    text-decoration: none;
    background: var(--bg-card);
    border: 1px solid var(--border);
    transition: all 0.2s;
}

.page-link:hover {
    color: var(--text-main);
    border-color: var(--primary);
}

.page-link.active {
    background: var(--primary);
    color: white;
    border-color: var(--primary);
}

.page-info {
    margin-left: 16px;
    font-size: 0.85rem;
    color: var(--text-muted);
}

@@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>
