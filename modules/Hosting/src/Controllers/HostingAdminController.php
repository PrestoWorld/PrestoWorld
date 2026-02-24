<?php

declare(strict_types=1);

namespace Modules\Hosting\Controllers;

use App\Foundation\Admin\AdminController;
use Witals\Framework\Http\Request;
use Witals\Framework\Http\Response;

class HostingAdminController extends AdminController
{
    public function index(Request $request): Response
    {
        $db = $this->db();
        $hostings = $db->select('*')->from('optilarity_hostings')->fetchAll();
        
        $rows = '';
        foreach ($hostings as $h) {
            $rows .= "<tr>
                <td><strong>{$h['domain']}</strong></td>
                <td>Member #{$h['customer_id']}</td>
                <td><span class='badge badge-active'>{$h['status']}</span></td>
                <td>{$h['expiry_date']}</td>
                <td>
                    <button class='btn-ghost-sm'>Dừng</button>
                    <button class='btn-ghost-sm'>Gia hạn</button>
                </td>
            </tr>";
        }

        if (empty($hostings)) {
            $rows = "<tr><td colspan='5' style='text-align:center; padding: 40px; color: #64748b;'>Chưa có dịch vụ hosting nào được kích hoạt.</td></tr>";
        }

        $content = <<<HTML
        <div class="presto-card">
            <div class="presto-card-header">
                <h2 class="presto-card-title">Quản lý Dịch vụ Hosting</h2>
                <div class="card-actions">
                    <a href="/dashboard/hosting/plans" class="btn-ghost-sm">📦 Quản lý Gói cước</a>
                </div>
            </div>
            <div class="presto-card-body p-0">
                <table class="presto-list-table">
                    <thead>
                        <tr>
                            <th>Domain</th>
                            <th>Khách hàng</th>
                            <th>Trạng thái</th>
                            <th>Hết hạn</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        {$rows}
                    </tbody>
                </table>
            </div>
        </div>
HTML;

        return Response::html($this->adminPage('Hosting Services', $content, [
            'new_label' => '+ Kích hoạt Hosting',
            'new_url' => '/dashboard/hosting/create'
        ]));
    }

    public function plans(Request $request): Response
    {
        $content = "<h2>Hosting Plans Management</h2><p>Coming soon...</p>";
        return Response::html($this->adminPage('Hosting Plans', $content));
    }
}
