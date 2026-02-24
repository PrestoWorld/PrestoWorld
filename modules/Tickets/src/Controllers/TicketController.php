<?php

declare(strict_types=1);

namespace Modules\Tickets\Controllers;

use Witals\Framework\Http\Request;
use Witals\Framework\Http\Response;
use Cycle\Database\DatabaseProviderInterface;
use Witals\Framework\Container\Container;

class TicketController
{
    protected DatabaseProviderInterface $dbal;
    protected Container $app;

    public function __construct(Container $app)
    {
        $this->app = $app;
        $this->dbal = $app->make(DatabaseProviderInterface::class);
    }

    public function index(Request $request): Response
    {
        $themeManager = $this->app->make(\PrestoWorld\Theme\ThemeManager::class);
        
        // Mock tickets for current customer
        $tickets = [
            [
                'id' => 101,
                'subject' => 'Lỗi không truy cập được Hosting',
                'status' => 'open',
                'priority' => 'high',
                'last_update' => '2 giờ trước',
                'category' => 'Technical Support'
            ],
            [
                'id' => 105,
                'subject' => 'Hỏi về gia hạn tên miền .top',
                'status' => 'answered',
                'priority' => 'medium',
                'last_update' => '1 ngày trước',
                'category' => 'Billing'
            ]
        ];

        $html = $themeManager->render('portal', [
            'page' => [
                'title' => 'Hỗ trợ & Tickets',
                'content' => $this->renderTicketList($tickets)
            ],
            'hide_sidebar' => true
        ]);

        return Response::html($html);
    }

    protected function renderTicketList(array $tickets): string
    {
        $rows = '';
        foreach ($tickets as $t) {
            $statusClass = $t['status'] === 'open' ? 'status-open' : 'status-answered';
            $rows .= "
            <div class='ticket-row' onclick=\"window.location='/portal/tickets/{$t['id']}'\">
                <div class='ticket-info'>
                    <span class='ticket-id'>#{$t['id']}</span>
                    <h4>{$t['subject']}</h4>
                    <div class='ticket-meta'>
                        <span class='meta-item'>{$t['category']}</span>
                        <span class='meta-item'>Cập nhật: {$t['last_update']}</span>
                    </div>
                </div>
                <div class='ticket-status-wrap'>
                    <span class='priority-badge priority-{$t['priority']}'>{$t['priority']}</span>
                    <span class='status-badge {$statusClass}'>{$t['status']}</span>
                </div>
            </div>";
        }

        return "
        <div class='ticket-container'>
            <div class='ticket-header-boltz'>
                <div class='header-left'>
                    <p>Theo dõi và quản lý các yêu cầu hỗ trợ kỹ thuật.</p>
                </div>
                <a href='/portal/tickets/create' class='btn-create-boltz'>+ Mở Ticket mới</a>
            </div>

            <div class='ticket-list'>
                {$rows}
            </div>
        </div>

        <style>
            .ticket-header-boltz { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; padding-bottom: 20px; border-bottom: 1px solid #E0E5F2; }
            .ticket-header-boltz p { color: #475467; font-size: 14px; margin: 0; font-weight: 500; }
            
            .btn-create-boltz { background: #4318FF; color: #fff; padding: 10px 20px; border-radius: 12px; text-decoration: none; font-weight: 700; font-size: 14px; transition: 0.3s; }
            .btn-create-boltz:hover { background: #3311CC; transform: translateY(-2px); }

            .ticket-list { display: flex; flex-direction: column; gap: 12px; }
            .ticket-row { background: #fff; border: 1px solid #E0E5F2; padding: 20px 25px; border-radius: 20px; display: flex; justify-content: space-between; align-items: center; cursor: pointer; transition: 0.3s; }
            .ticket-row:hover { border-color: #4318FF; transform: translateX(5px); box-shadow: 0 10px 20px rgba(0,0,0,0.02); }

            .ticket-info h4 { font-size: 16px; margin: 4px 0 6px; color: #1B2559; font-weight: 700; }
            .ticket-id { font-size: 11px; color: #4318FF; font-weight: 800; }
            .ticket-meta { display: flex; gap: 15px; font-size: 12px; color: #475467; font-weight: 500; }

            .ticket-status-wrap { display: flex; align-items: center; gap: 12px; }
            .status-badge { padding: 4px 10px; border-radius: 20px; font-size: 10px; font-weight: 800; text-transform: uppercase; }
            .status-open { background: #FFF5F5; color: #D32F2F; border: 1px solid #FFDADA; } /* Darker red */
            .status-answered { background: #E6FFF5; color: #008155; border: 1px solid #C8F7E4; } /* Darker green */
            
            .priority-badge { font-size: 9px; font-weight: 800; text-transform: uppercase; padding: 2px 6px; border-radius: 4px; }
            .priority-high { background: #D32F2F; color: #fff; }
            .priority-medium { background: #F57C00; color: #fff; }
        </style>
        ";
    }

    public function create(Request $request): Response
    {
        $themeManager = $this->app->make(\PrestoWorld\Theme\ThemeManager::class);
        $content = "
        <div class='ticket-create-boltz'>
            <form action='/portal/tickets/create' method='POST' class='boltz-form'>
                <div class='form-group'>
                    <label>Tiêu đề yêu cầu</label>
                    <input type='text' name='subject' placeholder='Nhập tiêu đề ngắn gọn...'>
                </div>
                <div class='form-grid'>
                    <div class='form-group'>
                        <label>Bộ phận hỗ trợ</label>
                        <select name='category'>
                            <option value='tech'>Kỹ thuật</option>
                            <option value='billing'>Thanh toán & Hóa đơn</option>
                            <option value='sales'>Kinh doanh</option>
                        </select>
                    </div>
                    <div class='form-group'>
                        <label>Mức độ ưu tiên</label>
                        <select name='priority'>
                            <option value='low'>Thấp</option>
                            <option value='medium' selected>Trung bình</option>
                            <option value='high'>Cao</option>
                            <option value='critical'>Khẩn cấp</option>
                        </select>
                    </div>
                </div>
                <div class='form-group'>
                    <label>Mô tả chi tiết</label>
                    <textarea name='message' rows='6' placeholder='Mô tả chi tiết vấn đề bạn đang gặp phải...'></textarea>
                </div>
                <div class='form-actions'>
                    <a href='/portal/tickets' class='btn-cancel'>Hủy bỏ</a>
                    <button type='submit' class='btn-submit-boltz'>Gửi Ticket</button>
                </div>
            </form>
        </div>
        <style>
            .boltz-form { margin-top: 10px; }
            .form-group { margin-bottom: 20px; }
            .form-group label { display: block; margin-bottom: 8px; font-size: 13px; font-weight: 700; color: #1B2559; }
            .form-group input, .form-group select, .form-group textarea { 
                width: 100%; 
                background: #F4F7FE; 
                border: 1px solid #E0E5F2; 
                padding: 12px 18px; 
                border-radius: 12px; 
                color: #1B2559; 
                font-size: 14px;
                outline: none;
                transition: 0.2s;
            }
            .form-group input:focus, .form-group select:focus, .form-group textarea:focus { border-color: #4318FF; background: #fff; }
            .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
            .form-actions { display: flex; justify-content: flex-end; gap: 15px; margin-top: 30px; }
            .btn-cancel { padding: 12px 20px; color: #A3AED0; text-decoration: none; font-weight: 700; font-size: 14px; }
            .btn-submit-boltz { background: #4318FF; border: none; padding: 12px 30px; border-radius: 12px; color: #fff; font-weight: 700; cursor: pointer; transition: 0.3s; }
            .btn-submit-boltz:hover { background: #3311CC; transform: scale(1.02); }
        </style>
        ";
        
        return Response::html($themeManager->render('portal', [
            'page' => ['title' => 'Mở Ticket', 'content' => $content],
            'hide_sidebar' => true
        ]));
    }

    public function store(Request $request): Response
    {
        // Add logic to save to DB later
        return Response::redirect('/portal/tickets');
    }

    public function show(Request $request, int $id): Response
    {
        $themeManager = $this->app->make(\PrestoWorld\Theme\ThemeManager::class);
        $content = "Ticket detail #{$id} view coming soon...";
        return Response::html($themeManager->render('portal', [
            'page' => ['title' => "Ticket #{$id}", 'content' => $content],
            'hide_sidebar' => true
        ]));
    }

    public function reply(Request $request, int $id): Response
    {
        return Response::redirect("/portal/tickets/{$id}");
    }
}
