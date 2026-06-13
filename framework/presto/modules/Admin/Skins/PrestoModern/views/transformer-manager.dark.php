<div class="presto-admin-layout">
    <header class="presto-header">
        <h1>Transformer Manager</h1>
        <div class="header-actions">
            <button class="btn btn-primary" id="fetch-from-repo">Fetch from Marketplace</button>
        </div>
    </header>

    <section class="presto-content">
        <div class="stats-grid">
            <div class="stat-card">
                <h3>Total Registered</h3>
                <span class="count">{{ count($transformers) }}</span>
            </div>
            <div class="stat-card">
                <h3>Active</h3>
                <span class="count">{{ count($active_themes) }}</span>
            </div>
        </div>

        <table class="presto-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Group</th>
                    <th>Keywords</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($transformers as $keyword => $classes)
                    @foreach($classes as $class)
                    <tr>
                        <td><code>{{ basename(str_replace('\\', '/', $class)) }}</code></td>
                        <td><span class="badge">Built-in</span></td>
                        <td>
                            @foreach(array_slice((array)$keyword, 0, 3) as $k)
                                <code class="tag">{{ $k }}</code>
                            @endforeach
                        </td>
                        <td><span class="status-active">Enabled</span></td>
                        <td>
                            <button class="btn-sm">Configure</button>
                            <button class="btn-sm btn-danger">Disable</button>
                        </td>
                    </tr>
                    @endforeach
                @endforeach
            </tbody>
        </table>
    </section>
</div>

<style>
    .tag { background: #eee; padding: 2px 5px; border-radius: 3px; font-size: 0.8em; margin-right: 2px; }
    .status-active { color: green; font-weight: bold; }
    .count { font-size: 2em; display: block; }
    .stat-card { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
    .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 20px; }
</style>
