<div class="admin-header-actions" style="margin-bottom: 2rem; display: flex; justify-content: space-between; align-items: baseline;">
    <h2 style="font-size: 1.8rem; font-weight: 800; letter-spacing: -0.04em;">User Management <span style="font-size: 0.9rem; font-weight: 400; color: var(--text-muted); vertical-align: middle; margin-left: 8px;">{{ count($users) }} Users</span></h2>
    <div style="display: flex; gap: 1rem;">
        <button class="presto-btn presto-btn-primary">+ Add New User</button>
        <input type="text" class="presto-input" placeholder="Search users..." style="min-width: 300px;">
    </div>
</div>

<div class="admin-table-container">
    <table class="admin-table">
        <thead>
            <tr>
                <th width="50">ID</th>
                <th>User Details</th>
                <th>Role</th>
                <th>Status</th>
                <th width="120">Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($users as $user)
            <tr>
                <td>{{ $user['id'] }}</td>
                <td>
                    <div style="display: flex; align-items: center; gap: 1rem;">
                        <div style="width: 40px; height: 40px; border-radius: 50%; background: var(--primary); display: flex; align-items: center; justify-content: center; color: white; font-weight: 700; font-size: 14px;">
                            {{ strtoupper(substr($user['name'], 0, 1)) }}
                        </div>
                        <div>
                            <div style="font-weight: 700; color: var(--text-main); font-size: 0.95rem;">{{ $user['name'] }}</div>
                            <div style="font-size: 0.85rem; color: var(--text-muted);">{{ $user['email'] }}</div>
                        </div>
                    </div>
                </td>
                <td>
                    <span class="presto-badge" style="background: rgba(255, 255, 255, 0.05); color: var(--text-muted);">Administrator</span>
                </td>
                <td>
                    <div style="display: flex; align-items: center; gap: 6px; font-size: 0.85rem; color: #10b981;">
                        <span style="width: 8px; height: 8px; border-radius: 50%; background: #10b981;"></span>
                        Active
                    </div>
                </td>
                <td>
                    <div style="display: flex; gap: 0.5rem; justify-content: flex-end;">
                        <button class="presto-btn-icon" title="Edit User">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7m-1.5-5.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                        </button>
                        <button class="presto-btn-icon" title="Delete User" style="color: #ef4444;">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18m-2 0v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                        </button>
                    </div>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>

<style>
.admin-table-container {
    background: rgba(255, 255, 255, 0.02);
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 12px;
    overflow: hidden;
}

.admin-table {
    width: 100%;
    border-collapse: collapse;
}

.admin-table th {
    background: rgba(255, 255, 255, 0.03);
    padding: 1.25rem 1.5rem;
    text-align: left;
    font-size: 0.8rem;
    text-transform: uppercase;
    color: var(--text-muted);
    letter-spacing: 0.1em;
    font-weight: 700;
}

.admin-table td {
    padding: 1.25rem 1.5rem;
    border-bottom: 1px solid rgba(255, 255, 255, 0.05);
}

.admin-table tr:hover td {
    background: rgba(255, 255, 255, 0.01);
}

.presto-btn-icon {
    width: 32px;
    height: 32px;
    border-radius: 6px;
    border: none;
    background: rgba(255, 255, 255, 0.05);
    color: var(--text-muted);
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s;
}

.presto-btn-icon:hover {
    background: rgba(255, 255, 255, 0.1);
    color: var(--text-main);
}
</style>
