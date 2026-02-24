<?php

declare(strict_types=1);

namespace Modules\Domains\Controllers;

use App\Foundation\Admin\AdminController;
use Witals\Framework\Http\Request;
use Witals\Framework\Http\Response;

class DomainsAdminController extends AdminController
{
    public function index(Request $request): Response
    {
        $db = $this->db();
        $domains = $db->select('*')->from('optilarity_domains')->fetchAll();
        
        $rows = '';
        foreach ($domains as $d) {
            $rows .= "<tr>
                <td><strong>{$d['domain_name']}</strong></td>
                <td>{$d['registrar']}</td>
                <td><span class='badge badge-active'>{$d['status']}</span></td>
                <td>{$d['expiry_date']}</td>
                <td>
                    <button class='btn-ghost-sm'>DNS</button>
                    <button class='btn-ghost-sm'>Gia hạn</button>
                </td>
            </tr>";
        }

        if (empty($domains)) {
            $rows = "<tr><td colspan='5' style='text-align:center; padding: 40px; color: #64748b;'>Chưa có tên miền nào được đăng ký.</td></tr>";
        }

        $content = <<<HTML
        <div class="presto-card">
            <div class="presto-card-header">
                <h2 class="presto-card-title">Quản lý Tên miền</h2>
            </div>
            <div class="presto-card-body p-0">
                <table class="presto-list-table">
                    <thead>
                        <tr>
                            <th>Tên miền</th>
                            <th>Nhà đăng ký</th>
                            <th>Trạng thái</th>
                            <th>Ngày hết hạn</th>
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

        return Response::html($this->adminPage('Domain Management', $content, [
            'new_label' => '+ Đăng ký Tên miền',
            'new_url' => '/dashboard/domains/create'
        ]));
    }

    public function create(Request $request): Response
    {
        $content = "<h2>Register New Domain</h2><p>Coming soon...</p>";
        return Response::html($this->adminPage('Domain Register', $content));
    }
}
