<?php

declare(strict_types=1);

namespace Modules\CustomerPortal\Controllers;

use Witals\Framework\Http\Request;
use Witals\Framework\Http\Response;
use Cycle\Database\DatabaseProviderInterface;
use Witals\Framework\Container\Container;

class PortalController
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
        
        $stats = [
            'services' => 3,
            'domains' => 1,
            'tickets' => 2,
            'invoices_unpaid' => 0
        ];

        $html = $themeManager->render('portal', [
            'page' => [
                'title' => 'Customer Portal',
                'content' => $this->renderDashboard($stats)
            ],
            'hide_sidebar' => true
        ]);

        return Response::html($html);
    }

    protected function renderDashboard(array $stats): string
    {
        return "
        <div class='dashboard-welcome-banner'>
            <div class='banner-left'>
                <h2>Chào mừng trở lại, Alexander!</h2>
                <p>Mọi dịch vụ của bạn đều đang hoạt động ổn định. Bạn đang có <strong>{$stats['services']}</strong> dịch vụ kích hoạt.</p>
            </div>
            <a href='/portal/services' class='btn-manage'>Quản lý dịch vụ</a>
        </div>

        <div class='boltz-card'>
            <div class='boltz-card-header'>
                <h3>Sản phẩm & Dịch vụ mới nhất</h3>
                <a href='/portal/services' class='view-all'>Xem tất cả</a>
            </div>
            <div class='service-list-boltz'>
                <div class='service-item-boltz'>
                    <div class='s-icon'>⚡</div>
                    <div class='s-info'>
                        <strong>WordPress Hosting Pro</strong>
                        <span>example.com • Hết hạn: 12/2024</span>
                    </div>
                    <span class='s-badge active'>Active</span>
                </div>
                <div class='service-item-boltz'>
                    <div class='s-icon'>🛡️</div>
                    <div class='s-info'>
                        <strong>EcomBuilder Theme License</strong>
                        <span>Vĩnh viễn • Version 2.4.0</span>
                    </div>
                    <span class='s-badge active'>Active</span>
                </div>
            </div>
        </div>

        <style>
            .dashboard-welcome-banner { 
                background: linear-gradient(90deg, #4318FF 0%, #707EAE 100%); 
                padding: 35px 40px; 
                border-radius: 30px; 
                color: white; 
                display: flex; 
                justify-content: space-between; 
                align-items: center; 
                margin-bottom: 30px;
            }
            .dashboard-welcome-banner h2 { font-size: 26px; font-weight: 800; margin: 0 0 8px; }
            .dashboard-welcome-banner p { margin: 0; opacity: 0.95; font-size: 15px; font-weight: 500; }
            .btn-manage { background: rgba(255,255,255,0.2); backdrop-filter: blur(10px); color: white; padding: 12px 24px; border-radius: 12px; text-decoration: none; font-weight: 700; border: 1px solid rgba(255,255,255,0.3); transition: 0.3s; }
            .btn-manage:hover { background: white; color: #4318FF; }

            .boltz-card-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; }
            .boltz-card-header h3 { font-size: 20px; font-weight: 800; color: #1B2559; margin: 0; }
            .view-all { color: #4318FF; font-size: 14px; font-weight: 700; text-decoration: none; }

            .service-item-boltz { display: flex; align-items: center; gap: 20px; padding: 15px 0; border-bottom: 1px solid #F4F7FE; }
            .service-item-boltz:last-child { border: none; }
            .service-item-boltz .s-icon { width: 48px; height: 48px; background: #F4F7FE; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 20px; }
            .service-item-boltz .s-info { flex: 1; }
            .service-item-boltz .s-info strong { display: block; font-size: 16px; color: #1B2559; margin-bottom: 2px; }
            .service-item-boltz .s-info span { font-size: 13px; color: #475467; font-weight: 500; } /* Darker gray */
            .s-badge { padding: 4px 12px; border-radius: 20px; font-size: 11px; font-weight: 800; text-transform: uppercase; }
            .s-badge.active { background: #E6FFF5; color: #008155; } /* Darker green for contrast */
        </style>
        ";
    }

    public function services(): Response { return Response::html("Services management view coming soon..."); }
    public function billing(): Response { return Response::html("Billing history view coming soon..."); }
    public function profile(): Response { return Response::html("Profile settings view coming soon..."); }
    public function updateProfile(Request $request): Response { return Response::redirect('/portal/profile'); }
}
