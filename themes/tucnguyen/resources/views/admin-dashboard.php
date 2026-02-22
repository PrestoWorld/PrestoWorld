<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DigitalCore Admin - Dashboard</title>
    <link rel="stylesheet" href="/css/admin-dashboard.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body>
    <aside>
        <div class="sidebar-logo">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <rect width="24" height="24" rx="6" fill="#3B82F6"/>
                <path d="M12 8V16M8 12H16" stroke="white" stroke-width="2" stroke-linecap="round"/>
            </svg>
            Digital<span>Core.</span>
        </div>

        <div class="nav-section">
            <div class="nav-label">SẢN PHẨM & LICENSE</div>
            <div class="nav-item active">📊 Dashboard</div>
            <div class="nav-item">🎨 Themes Manager</div>
            <div class="nav-item">🔌 Plugins Repository</div>
            <div class="nav-item">💻 Software Licenses</div>
            <div class="nav-item">👑 Gói Thành Viên</div>
        </div>

        <div class="nav-section">
            <div class="nav-label">KINH DOANH</div>
            <div class="nav-item">🔑 License Keys</div>
            <div class="nav-item">🛒 Đơn hàng</div>
            <div class="nav-item">👤 Khách hàng</div>
            <div class="nav-item">📄 Hóa đơn</div>
        </div>

        <div class="nav-section">
            <div class="nav-label">HỆ THỐNG</div>
            <div class="nav-item">/&gt; API Keys (Updater)</div>
            <div class="nav-item">🔗 Webhooks</div>
        </div>

        <div class="sidebar-footer">
            <div class="user-profile">
                <div class="user-avatar"></div>
                <div class="user-info">
                    <div class="user-name">Alexander Dev</div>
                    <div class="user-role">Super Admin</div>
                </div>
                <div style="margin-left: auto;">🚪</div>
            </div>
        </div>
    </aside>

    <main>
        <div class="dashboard-header">
            <div>
                <h1>Tổng quan kinh doanh</h1>
                <p style="color: var(--text-muted); font-size: 14px; margin-top: 4px;">Quản lý toàn bộ hệ sinh thái sản phẩm số của bạn.</p>
            </div>
            <div class="header-tools">
                <div class="search-field">
                    🔍 <input type="text" placeholder="Tìm kiếm License key, tên khách hàng...">
                </div>
                <button class="btn-new">+ Tạo License Mới</button>
            </div>
        </div>

        <!-- Stats Grid -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-label">Tổng Doanh Thu</div>
                <div class="stat-value">$124,500</div>
                <div class="stat-trend">+12% vs tháng trước</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Active Licenses</div>
                <div class="stat-value">8,240</div>
                <div style="color: var(--text-muted); font-size: 12px;">Đang kích hoạt trên 1.5k domains</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Thành Viên VIP</div>
                <div class="stat-value">1,205</div>
                <div style="color: var(--text-muted); font-size: 12px;">Active Members (Recurring)</div>
            </div>
            <div class="stat-card" style="border-left: 4px solid var(--accent-red);">
                <div class="stat-label">Sắp hết hạn (30 ngày)</div>
                <div class="stat-value" style="color: var(--accent-red);">45</div>
                <div style="color: var(--text-muted); font-size: 12px;">Cần gia hạn gấp</div>
            </div>
        </div>

        <!-- Product Categories -->
        <h2 style="font-size: 18px; margin-bottom: 24px;">📂 Danh mục Sản phẩm</h2>
        <div class="categories-grid">
            <div class="cat-card">
                <div class="cat-icon blue">🎨</div>
                <h3>WordPress Themes</h3>
                <div class="cat-meta"><span>Sản phẩm</span> <b>15 Themes</b></div>
                <div class="cat-meta"><span>Lượt tải</span> <b>2.3k</b></div>
                <div style="height: 4px; background: #262626; border-radius: 2px; margin-top: 20px;">
                    <div style="width: 78%; height: 100%; background: var(--accent-purple); border-radius: 2px;"></div>
                </div>
            </div>
            <div class="cat-card">
                <div class="cat-icon" style="color: var(--accent-purple);">🔌</div>
                <h3>Plugins Repository</h3>
                <div class="cat-meta"><span>Đang hoạt động</span> <b>8 Plugins</b></div>
                <div class="cat-meta"><span>Phiên bản mới nhất</span> <b>v2.4.0</b></div>
                <div class="cat-footer">🚀 Đẩy bản cập nhật</div>
            </div>
            <div class="cat-card">
                <div class="cat-icon" style="color: var(--accent);">💻</div>
                <h3>Desktop Softwares</h3>
                <div class="cat-meta"><span>License vĩnh viễn</span> <b>400</b></div>
                <div class="cat-meta"><span>License theo năm</span> <b>1.2k</b></div>
                <div class="cat-footer">+ Quản lý versions</div>
            </div>
            <div class="cat-card">
                <div class="cat-icon" style="color: #facc15;">👑</div>
                <h3>Gói Membership</h3>
                <div class="cat-meta"><span>Starter</span> <b>100 users</b></div>
                <div class="cat-meta"><span>Pro</span> <b>500 users</b></div>
                <div class="cat-meta"><span>Agency</span> <b>200 users</b></div>
                <div class="cat-footer">⚙️ Cấu hình gói</div>
            </div>
        </div>

        <!-- Content Grid -->
        <div class="content-grid">
            <div class="table-container">
                <div class="table-header">
                    <div style="font-weight: 700;">🛡️ Giao dịch License mới nhất</div>
                    <div style="font-size: 12px; display: flex; gap: 12px;">
                        <span style="color: var(--accent);">Tất cả</span>
                        <span>Software</span>
                        <span>Theme</span>
                    </div>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>Sản phẩm</th>
                            <th>License Key</th>
                            <th>Khách hàng</th>
                            <th>Domain / Device</th>
                            <th>Trạng thái</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><b>EcomBuilder Theme</b><br><span style="font-size: 10px; color: var(--accent-purple);">Theme</span></td>
                            <td style="font-family: monospace;">7A8E-9CD0... 📋</td>
                            <td><b>Nguyen Van A</b><br><span style="font-size: 10px; color: var(--text-muted);">nguyenva@studio.com</span></td>
                            <td>🌐 shop.client-site.com</td>
                            <td><span class="badge-status badge-active">• Active</span></td>
                        </tr>
                        <tr>
                            <td><b>SEO Pro Plugin</b><br><span style="font-size: 10px; color: var(--accent);">Plugin</span></td>
                            <td style="font-family: monospace;">3X4Y-5Z6A... 📋</td>
                            <td><b>Sarah Smith</b><br><span style="font-size: 10px; color: var(--text-muted);">sarah.dev@agency.co</span></td>
                            <td>🌐 tech-blog.net</td>
                            <td><span class="badge-status badge-active">• Active</span></td>
                        </tr>
                        <tr>
                            <td><b>RenderMax Soft</b><br><span style="font-size: 10px; color: var(--accent-red);">Software</span></td>
                            <td style="font-family: monospace;">1A2B-3C4D... 📋</td>
                            <td><b>Michael Chen</b><br><span style="font-size: 10px; color: var(--text-muted);">chen.m@render.io</span></td>
                            <td>💻 Device: MAC-M2-...</td>
                            <td><span class="badge-status badge-expired">• Expired</span></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="chart-container">
                <div class="chart-title">Nguồn doanh thu</div>
                <div class="doughnut-placeholder">
                    <div style="text-align: center;">
                        <div style="font-size: 12px; color: var(--text-muted);">Trung bình</div>
                        <div style="font-size: 20px; font-weight: 800;">$2.4k</div>
                    </div>
                </div>
                <div class="doughnut-meta">
                    <div class="meta-item">
                        <span><span class="dot blue"></span> Membership</span>
                        <b>40%</b>
                    </div>
                    <div class="meta-item">
                        <span><span class="dot green"></span> Themes</span>
                        <b>30%</b>
                    </div>
                    <div class="meta-item">
                        <span><span class="dot purple"></span> Plugins</span>
                        <b>20%</b>
                    </div>
                    <div class="meta-item">
                        <span><span class="dot orange"></span> Software</span>
                        <b>10%</b>
                    </div>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
