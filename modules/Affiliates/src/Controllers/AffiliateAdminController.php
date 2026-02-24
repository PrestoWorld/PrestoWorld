<?php

declare(strict_types=1);

namespace Modules\Affiliates\Controllers;

use App\Foundation\Admin\AdminController;
use Witals\Framework\Http\Request;
use Witals\Framework\Http\Response;

class AffiliateAdminController extends AdminController
{
    public function index(Request $request): Response
    {
        $db = $this->db();
        
        // Mock stats for admin overview
        $totalAffiliates = 150;
        $pendingWithdrawals = 5;
        $totalCommissionPaid = "12,500,000 VND";

        $content = <<<HTML
        <div class="presto-dashboard-grid">
            <div class="presto-card stat-card-premium">
                <div class="stat-main">
                    <span class="stat-label">Tổng số Cộng tác viên</span>
                    <span class="stat-value">{$totalAffiliates}</span>
                </div>
                <div class="stat-visual"><span class="stat-icon">👥</span></div>
            </div>
            <div class="presto-card stat-card-premium warning">
                <div class="stat-main">
                    <span class="stat-label">Yêu cầu rút tiền chờ xử lý</span>
                    <span class="stat-value">{$pendingWithdrawals}</span>
                </div>
                <div class="stat-visual"><span class="stat-icon">💸</span></div>
            </div>
            <div class="presto-card stat-card-premium info">
                <div class="stat-main">
                    <span class="stat-label">Tổng hoa hồng đã chi trả</span>
                    <span class="stat-value">{$totalCommissionPaid}</span>
                </div>
                <div class="stat-visual"><span class="stat-icon">📊</span></div>
            </div>
        </div>

        <div class="presto-card mt-32">
            <div class="presto-card-header">
                <h2 class="presto-card-title">Cộng tác viên mới nhất</h2>
            </div>
            <div class="presto-card-body p-0">
                <table class="presto-list-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Tên</th>
                            <th>Email</th>
                            <th>Số dư</th>
                            <th>Trạng thái</th>
                            <th>Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>#3876</td>
                            <td><strong>Nguyễn Văn A</strong></td>
                            <td>affiliate@example.com</td>
                            <td>20,000 VND</td>
                            <td><span class="badge badge-active">Active</span></td>
                            <td><button class="btn-ghost-sm">Chi tiết</button></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
HTML;

        return $this->htmlResponse($this->adminPage('Affiliate Management', $content));
    }

    public function commissions(Request $request): Response
    {
        return $this->htmlResponse($this->adminPage('Affiliate Commissions', '<p>Commission history management coming soon.</p>'));
    }
}
