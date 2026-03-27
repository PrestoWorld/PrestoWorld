@php
$indentWidth = $level * 24;
$hasChildren = !empty($node['children']);
@endphp

<tr>
    <td>
        <div class="category-name-cell">
            <div class="category-indent" style="width: {{ $indentWidth }}px;">
                @if($hasChildren)
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="m9 18 6-6-6-6"/>
                </svg>
                @endif
            </div>
            <div class="category-info">
                <div class="category-name">{{ $node['name'] }}</div>
                @if($node['description'])
                <div class="category-description">{{ Str::limit($node['description'], 60) }}</div>
                @endif
            </div>
        </div>
    </td>
    <td>
        <code class="slug-code">{{ $node['slug'] }}</code>
    </td>
    <td>
        <span class="status-badge {{ $node['is_active'] ? 'status-active' : 'status-inactive' }}">
            {{ $node['is_active'] ? 'Active' : 'Inactive' }}
        </span>
    </td>
    <td>{{ $node['sort_order'] }}</td>
    <td>
        <div class="action-buttons">
            <a href="/dashboard/categories/edit/{{ $node['id'] }}" class="btn-action btn-edit" title="Edit">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                </svg>
            </a>
            <form method="POST" action="/dashboard/categories/delete/{{ $node['id'] }}" style="display: inline;" onsubmit="return confirm('Delete this category?');">
                <button type="submit" class="btn-action btn-delete" title="Delete">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="3 6 5 6 21 6"/>
                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/>
                        <path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                    </svg>
                </button>
            </form>
        </div>
    </td>
</tr>

@if($hasChildren)
    @foreach($node['children'] as $child)
        @include('admin/categories/_category-row', ['node' => $child, 'level' => $level + 1])
    @endforeach
@endif
