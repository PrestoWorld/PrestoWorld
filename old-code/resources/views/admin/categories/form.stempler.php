<div class="category-form-page">
    <form method="POST" action="{{ $isEdit ? '/dashboard/categories/update/' . $category->getId() : '/dashboard/categories/store' }}" class="presto-form">
        @if(!empty($error))
        <div class="alert alert-danger">{{ $error }}</div>
        @endif

        <div class="form-row">
            <div class="form-group">
                <label for="name">Category Name *</label>
                <input type="text" id="name" name="name" class="presto-input" 
                       value="{{ $category?->getName() ?? $_POST['name'] ?? '' }}" required>
            </div>

            <div class="form-group">
                <label for="slug">Slug</label>
                <input type="text" id="slug" name="slug" class="presto-input" 
                       value="{{ $category?->getSlug() ?? $_POST['slug'] ?? '' }}" 
                       placeholder="auto-generated from name">
                <small>Leave empty to auto-generate from name</small>
            </div>
        </div>

        <div class="form-group">
            <label for="parent_id">Parent Category</label>
            <select id="parent_id" name="parent_id" class="presto-input">
                <option value="">-- None (Top Level) --</option>
                @foreach($parentCategories as $parentCat)
                    @if(!$isEdit || $parentCat->getId() !== $category->getId())
                    <option value="{{ $parentCat->getId() }}" 
                            {{ ($category?->getParentId() == $parentCat->getId()) ? 'selected' : '' }}>
                        {{ $parentCat->getName() }}
                    </option>
                    @endif
                @endforeach
            </select>
        </div>

        <div class="form-group">
            <label for="description">Description</label>
            <textarea id="description" name="description" class="presto-input" rows="4">{{ $category?->getDescription() ?? $_POST['description'] ?? '' }}</textarea>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="sort_order">Sort Order</label>
                <input type="number" id="sort_order" name="sort_order" class="presto-input" 
                       value="{{ $category?->getSortOrder() ?? $_POST['sort_order'] ?? 0 }}">
            </div>

            <div class="form-group form-check">
                <label class="checkbox-label">
                    <input type="checkbox" name="is_active" value="1" 
                           {{ ($category?->isActive() ?? true) ? 'checked' : '' }}>
                    <span>Active</span>
                </label>
            </div>
        </div>

        <div class="form-actions">
            <a href="/dashboard/categories" class="presto-btn presto-btn-secondary">Cancel</a>
            <button type="submit" class="presto-btn presto-btn-primary">
                {{ $isEdit ? 'Update Category' : 'Create Category' }}
            </button>
        </div>
    </form>
</div>

<style>
.category-form-page {
    max-width: 800px;
    animation: fadeIn 0.3s ease-out;
}

.presto-form {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
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
    margin-top: 20px;
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
