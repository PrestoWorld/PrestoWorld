<?php

declare(strict_types=1);

namespace Modules\Dashboard\Controllers;

use App\Foundation\Admin\AdminController;
use Witals\Framework\Http\Request;
use Witals\Framework\Http\Response;
use Cycle\Database\Injection\Fragment;

class DashboardController extends AdminController
{
    public function index(Request $request): Response
    {
        $stats = $this->fetchStats();
        
        $content = <<<HTML
        <div class="presto-dashboard-grid">
            <!-- Top Stats -->
            <div class="presto-card stat-card-premium">
                <div class="stat-main">
                    <span class="stat-label">Tổng Doanh Thu</span>
                    <span class="stat-value">\${$stats['revenue']}</span>
                    <span class="stat-trend trend-up">+12% vs tháng trước</span>
                </div>
                <div class="stat-visual">
                    <div class="stat-icon-wrap"><span class="stat-icon">💰</span></div>
                </div>
            </div>
            <div class="presto-card stat-card-premium">
                <div class="stat-main">
                    <span class="stat-label">Active Licenses</span>
                    <span class="stat-value">{$stats['licenses']}</span>
                    <span class="stat-trend">Đang kích hoạt trên 1.5k domains</span>
                </div>
                <div class="stat-visual">
                    <div class="stat-icon-wrap"><span class="stat-icon">🛡️</span></div>
                </div>
            </div>
            <div class="presto-card stat-card-premium">
                <div class="stat-main">
                    <span class="stat-label">Thành Viên VIP</span>
                    <span class="stat-value">1,205</span>
                    <span class="stat-trend">Active Members (Recurring)</span>
                </div>
                <div class="stat-visual">
                    <div class="stat-icon-wrap"><span class="stat-icon">👑</span></div>
                </div>
            </div>
            <div class="presto-card stat-card-premium danger">
                <div class="stat-main">
                    <span class="stat-label">Sắp hết hạn (30 ngày)</span>
                    <span class="stat-value">45</span>
                    <span class="stat-trend">Cần gia hạn gấp</span>
                </div>
                <div class="stat-visual">
                    <div class="stat-icon-wrap"><span class="stat-icon">⏳</span></div>
                </div>
            </div>
        </div>

        <h2 class="section-title">Danh mục Sản phẩm</h2>
        <div class="presto-category-grid">
            <div class="presto-card category-card">
                <div class="cat-header">
                    <div class="cat-icon" style="background: linear-gradient(135deg, #6366f1, #a855f7);">🎨</div>
                    <div class="cat-title-group">
                        <h3>WordPress Themes</h3>
                        <span class="badge badge-software">Theme</span>
                    </div>
                </div>
                <div class="cat-stats">
                    <div class="cat-stat"><span>Sản phẩm</span> <strong>15 Themes</strong></div>
                    <div class="cat-stat"><span>Lượt tải</span> <strong>2.3k</strong></div>
                </div>
                <div class="cat-progress">
                    <div class="progress-label"><span>Tỷ lệ gia hạn</span> <span>78%</span></div>
                    <div class="progress-bar"><div class="progress-fill" style="width: 78%"></div></div>
                </div>
            </div>
            <div class="presto-card category-card">
                <div class="cat-header">
                    <div class="cat-icon" style="background: linear-gradient(135deg, #3b82f6, #2dd4bf);">🔌</div>
                    <div class="cat-title-group">
                        <h3>Plugins Repository</h3>
                        <span class="badge badge-plugin">Plugin</span>
                    </div>
                </div>
                <div class="cat-stats">
                    <div class="cat-stat"><span>Đang hoạt động</span> <strong>8 Plugins</strong></div>
                    <div class="cat-stat"><span>Phiên bản mới nhất</span> <strong>v2.4.0</strong></div>
                </div>
                <div class="cat-footer">
                    <button class="btn-ghost-sm">🔔 Đẩy bản cập nhật</button>
                </div>
            </div>
            <div class="presto-card category-card">
                <div class="cat-header">
                    <div class="cat-icon" style="background: linear-gradient(135deg, #8b5cf6, #ec4899);">💻</div>
                    <div class="cat-title-group">
                        <h3>Desktop Softwares</h3>
                        <span class="badge badge-software">Software</span>
                    </div>
                </div>
                <div class="cat-stats">
                    <div class="cat-stat"><span>License vĩnh viễn</span> <strong>400</strong></div>
                    <div class="cat-stat"><span>License theo năm</span> <strong>1.2k</strong></div>
                </div>
                <div class="cat-footer">
                    <button class="btn-ghost-sm">+ Quản lý versions</button>
                </div>
            </div>
            <div class="presto-card category-card">
                <div class="cat-header">
                    <div class="cat-icon" style="background: linear-gradient(135deg, #f59e0b, #ef4444);">💎</div>
                    <div class="cat-title-group">
                        <h3>Gói Membership</h3>
                        <span class="badge badge-membership">Membership</span>
                    </div>
                </div>
                <div class="cat-stats">
                    <div class="cat-stat"><span>Starter</span> <strong>100 users</strong></div>
                    <div class="cat-stat"><span>Pro</span> <strong>500 users</strong></div>
                    <div class="cat-stat"><span>Agency</span> <strong>200 users</strong></div>
                </div>
                <div class="cat-footer">
                    <button class="btn-ghost-sm">⚙️ Cấu hình gói</button>
                </div>
            </div>
        </div>

        <div class="dashboard-bottom-row">
            <div class="presto-card">
                <div class="presto-card-header">
                    <h2 class="presto-card-title">Giao dịch License mới nhất</h2>
                    <div class="card-tabs">
                        <button class="tab-btn active">Tất cả</button>
                        <button class="tab-btn">Software</button>
                        <button class="tab-btn">Theme</button>
                        <button class="tab-btn">Plugin</button>
                    </div>
                </div>
                <div class="presto-card-body p-0">
                    <table class="presto-list-table">
                        <thead>
                            <tr>
                                <th>Sản phẩm</th>
                                <th>License Key</th>
                                <th>Khách hàng</th>
                                <th>Trạng thái</th>
                                <th>Hết hạn</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><strong>EcomBuilder Theme</strong></td>
                                <td><code>7A8B-5CDD...</code></td>
                                <td>Nguyen Van A</td>
                                <td><span class="badge badge-active">Active</span></td>
                                <td>24/10/2024</td>
                            </tr>
                            <tr>
                                <td><strong>SEO Pro Plugin</strong></td>
                                <td><code>3F4Y-5Z6A...</code></td>
                                <td>Sarah Smith</td>
                                <td><span class="badge badge-active">Active</span></td>
                                <td>15/11/2024</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="presto-card">
                <div class="presto-card-header"><h2 class="presto-card-title">Nguồn doanh thu</h2></div>
                <div class="presto-card-body" style="text-align: center;">
                    <div class="donut-chart-mock">
                        <div class="donut-inner">
                            <span class="donut-total">100%</span>
                        </div>
                    </div>
                    <ul class="chart-legend">
                        <li><span><span class="dot" style="background: #6366f1"></span> Membership</span> <span>40%</span></li>
                        <li><span><span class="dot" style="background: #10b981"></span> Themes</span> <span>30%</span></li>
                        <li><span><span class="dot" style="background: #f59e0b"></span> Plugins</span> <span>20%</span></li>
                    </ul>
                </div>
            </div>
        </div>
HTML;

        return Response::html($this->adminPage('Tổng quan kinh doanh', $content, [
            'new_label' => '+ Tạo License Mới',
            'new_url' => '/dashboard/licenses/create'
        ]));
    }

    private function fetchStats(): array
    {
        $db = $this->db();
        
        $customers = (int)$db->select()->from('optilarity_customers')->count('id');
        $orders    = (int)$db->select()->from('optilarity_orders')->count('id');
        $licenses  = (int)$db->select()->from('optilarity_licenses')->count('id');
        
        $revenue   = $db->select(new Fragment('SUM(total) as total'))->from('optilarity_orders')->run()->fetch();
        
        return [
            'customers' => number_format($customers),
            'orders'    => number_format($orders),
            'licenses'  => number_format($licenses),
            'revenue'   => number_format((float)($revenue['total'] ?? 0), 2),
        ];
    }
}
