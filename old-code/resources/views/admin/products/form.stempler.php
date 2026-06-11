<div class="product-form-page">
    <form method="POST" action="{{ $isEdit ? '/dashboard/products/update/' . $product->getId() : '/dashboard/products/store' }}" class="presto-form">
        @if(!empty($error))
        <div class="alert alert-danger">{{ $error }}</div>
        @endif

        <div class="form-section">
            <h3 class="section-title">Basic Information</h3>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="name">Product Name *</label>
                    <input type="text" id="name" name="name" class="presto-input" 
                           value="{{ $product?->getName() ?? $_POST['name'] ?? '' }}" required>
                </div>

                <div class="form-group">
                    <label for="slug">Slug</label>
                    <input type="text" id="slug" name="slug" class="presto-input" 
                           value="{{ $product?->getSlug() ?? $_POST['slug'] ?? '' }}"
                           placeholder="auto-generated from name">
                    <small>Leave empty to auto-generate from name</small>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="sku">SKU</label>
                    <input type="text" id="sku" name="sku" class="presto-input" 
                           value="{{ $product?->getSku() ?? $_POST['sku'] ?? '' }}"
                           placeholder="auto-generated">
                </div>

                <div class="form-group">
                    <label for="category_id">Category</label>
                    <select id="category_id" name="category_id" class="presto-input">
                        <option value="">-- Select Category --</option>
                        @foreach($categories as $cat)
                        <option value="{{ $cat->getId() }}" 
                                {{ ($product?->getCategoryId() == $cat->getId()) ? 'selected' : '' }}>
                            {{ $cat->getName() }}
                        </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label for="short_description">Short Description</label>
                <input type="text" id="short_description" name="short_description" class="presto-input"
                       value="{{ $product?->getShortDescription() ?? $_POST['short_description'] ?? '' }}"
                       placeholder="Brief description for product listing">
            </div>

            <div class="form-group">
                <label for="description">Full Description</label>
                <textarea id="description" name="description" class="presto-input" rows="6">{{ $product?->getDescription() ?? $_POST['description'] ?? '' }}</textarea>
            </div>
        </div>

        <div class="form-section">
            <h3 class="section-title">Pricing & Inventory</h3>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="price">Regular Price ($)</label>
                    <input type="number" id="price" name="price" class="presto-input" step="0.01" min="0"
                           value="{{ $product?->getPrice() ?? $_POST['price'] ?? '' }}">
                </div>

                <div class="form-group">
                    <label for="sale_price">Sale Price ($)</label>
                    <input type="number" id="sale_price" name="sale_price" class="presto-input" step="0.01" min="0"
                           value="{{ $product?->getSalePrice() ?? $_POST['sale_price'] ?? '' }}">
                    <small>Leave empty for no sale</small>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="stock">Stock Quantity</label>
                    <input type="number" id="stock" name="stock" class="presto-input" min="0"
                           value="{{ $product?->getStock() ?? $_POST['stock'] ?? 0 }}">
                </div>

                <div class="form-group">
                    <label for="type">Product Type</label>
                    <select id="type" name="type" class="presto-input">
                        <option value="simple" {{ ($product?->getType() ?? 'simple') === 'simple' ? 'selected' : '' }}>Simple Product</option>
                        <option value="variable" {{ ($product?->getType() ?? '') === 'variable' ? 'selected' : '' }}>Variable Product</option>
                        <option value="digital" {{ ($product?->getType() ?? '') === 'digital' ? 'selected' : '' }}>Digital Product</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="form-section">
            <h3 class="section-title">Settings</h3>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="status">Status</label>
                    <select id="status" name="status" class="presto-input">
                        <option value="active" {{ ($product?->getStatus() ?? 'draft') === 'active' ? 'selected' : '' }}>Active</option>
                        <option value="draft" {{ ($product?->getStatus() ?? 'draft') === 'draft' ? 'selected' : '' }}>Draft</option>
                        <option value="inactive" {{ ($product?->getStatus() ?? '') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>
            </div>

            <div class="form-group form-check">
                <label class="checkbox-label">
                    <input type="checkbox" name="is_featured" value="1" 
                           {{ ($product?->isFeatured() ?? false) ? 'checked' : '' }}>
                    <span>Featured Product</span>
                </label>
            </div>
        </div>

        <div class="form-actions">
            <a href="/dashboard/products" class="presto-btn presto-btn-secondary">Cancel</a>
            <button type="submit" class="presto-btn presto-btn-primary">
                {{ $isEdit ? 'Update Product' : 'Create Product' }}
            </button>
        </div>
    </form>
</div>

<style>
.product-form-page {
    max-width: 900px;
    animation: fadeIn 0.3s ease-out;
}

.presto-form {
    display: flex;
    flex-direction: column;
    gap: 32px;
}

.form-section {
    background: var(--bg-card);
    border: 1px solid var(--border);
    border-radius: 16px;
    padding: 24px;
}

.section-title {
    font-size: 1.1rem;
    font-weight: 700;
    color: var(--text-main);
    margin: 0 0 20px 0;
    padding-bottom: 12px;
    border-bottom: 1px solid var(--border);
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin-bottom: 20px;
}

.form-row:last-child {
    margin-bottom: 0;
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.form-group label {
    font-weight: 600;
    color: var(--text-main);
    font-size: 0.9rem;
}

.form-group small {
    color: var(--text-muted);
    font-size: 0.8rem;
}

.form-check {
    flex-direction: row;
    align-items: center;
    gap: 12px;
}

.checkbox-label {
    display: flex;
    align-items: center;
    gap: 10px;
    cursor: pointer;
}

.checkbox-label input[type="checkbox"] {
    width: 20px;
    height: 20px;
    accent-color: var(--primary);
}

.form-actions {
    display: flex;
    gap: 12px;
    justify-content: flex-end;
    padding-top: 20px;
    border-top: 1px solid var(--border);
}

.alert {
    padding: 12px 16px;
    border-radius: 10px;
    font-size: 0.9rem;
}

.alert-danger {
    background: rgba(239, 68, 68, 0.1);
    color: var(--danger);
    border: 1px solid rgba(239, 68, 68, 0.2);
}

@@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>
