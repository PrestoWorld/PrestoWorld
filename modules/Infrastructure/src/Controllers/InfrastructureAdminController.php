<?php

declare(strict_types=1);

namespace Modules\Infrastructure\Controllers;

use App\Foundation\Admin\AdminController;
use Witals\Framework\Http\Request;
use Witals\Framework\Http\Response;

class InfrastructureAdminController extends AdminController
{
    public function ssl(Request $request): Response
    {
        $db = $this->db();
        $certs = $db->select('*')->from('optilarity_ssl_certificates')->fetchAll();
        
        $rows = '';
        foreach ($certs as $c) {
            $rows .= "<tr>
                <td><strong>{$c['domain']}</strong></td>
                <td>{$c['provider']} ({$c['type']})</td>
                <td><span class='badge badge-active'>{$c['status']}</span></td>
                <td>{$c['expiry_date']}</td>
                <td><button class='btn-ghost-sm'>Chi tiết</button></td>
            </tr>";
        }

        if (empty($certs)) {
            $rows = "<tr><td colspan='5' style='text-align:center; padding: 40px; color: #64748b;'>Chưa có chứng chỉ SSL nào.</td></tr>";
        }

        $content = <<<HTML
        <div class="presto-card">
            <div class="presto-card-header">
                <h2 class="presto-card-title">Chứng chỉ SSL</h2>
            </div>
            <div class="presto-card-body p-0">
                <table class="presto-list-table">
                    <thead>
                        <tr>
                            <th>Domain</th>
                            <th>Nhà cung cấp</th>
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

        return Response::html($this->adminPage('SSL Certificates', $content));
    }

    public function email(Request $request): Response
    {
        $db = $this->db();
        $emails = $db->select('*')->from('optilarity_email_hosting')->fetchAll();
        
        $rows = '';
        foreach ($emails as $e) {
            $rows .= "<tr>
                <td><strong>{$e['domain']}</strong></td>
                <td>{$e['plan_name']} ({$e['mailbox_count']} mailboxes)</td>
                <td><span class='badge badge-active'>{$e['status']}</span></td>
                <td><button class='btn-ghost-sm'>Quản lý</button></td>
            </tr>";
        }

        if (empty($emails)) {
            $rows = "<tr><td colspan='4' style='text-align:center; padding: 40px; color: #64748b;'>Chưa có dịch vụ Email Hosting nào.</td></tr>";
        }

        $content = <<<HTML
        <div class="presto-card">
            <div class="presto-card-header">
                <h2 class="presto-card-title">Email Hosting</h2>
            </div>
            <div class="presto-card-body p-0">
                <table class="presto-list-table">
                    <thead>
                        <tr>
                            <th>Domain</th>
                            <th>Gói dịch vụ</th>
                            <th>Trạng thái</th>
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

        return Response::html($this->adminPage('Email Hosting', $content));
    }
}
