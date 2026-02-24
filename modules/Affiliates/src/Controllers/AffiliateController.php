<?php

declare(strict_types=1);

namespace Modules\Affiliates\Controllers;

use Witals\Framework\Http\Request;
use Witals\Framework\Http\Response;
use Cycle\Database\DatabaseProviderInterface;
use Witals\Framework\Container\Container;

class AffiliateController
{
    protected DatabaseProviderInterface $dbal;
    protected Container $app;

    public function __construct(Container $app)
    {
        $this->app = $app;
        $this->dbal = $app->make(DatabaseProviderInterface::class);
    }

    /**
     * Affiliate Center (Dashboard)
     */
    public function index(Request $request): Response
    {
        $themeManager = $this->app->make(\PrestoWorld\Theme\ThemeManager::class);
        
        // Mock data for now, in a real app this would come from DB linked to current user
        $stats = [
            'commission_month' => '0.00',
            'commission_total' => '0.00',
            'visits_month' => 0,
            'visits_total' => 0,
            'registrations_month' => 0,
            'registrations_total' => 0,
            'balance' => '20,000',
            'affid' => '3876',
        ];

        $campaigns = $this->getCampaigns($stats['affid']);

        $isPortal = str_starts_with($request->uri()->getPath(), '/portal');
        
        $html = $themeManager->render('portal', [
            'page' => [
                'title' => 'Affiliate Center',
                'content' => $this->renderAffiliateDashboard($stats, $campaigns, $isPortal)
            ],
            'hide_sidebar' => !$isPortal,
            'is_portal' => $isPortal
        ]);

        return Response::html($html);
    }

    protected function renderAffiliateDashboard(array $stats, array $campaigns, bool $isPortal = false): string
    {
        $campaignsHtml = '';
        foreach ($campaigns as $camp) {
            $campaignsHtml .= "
            <div class='aff-campaign-card'>
                <div class='camp-info'>
                    <h4>{$camp['name']}</h4>
                    <code class='camp-link'>{$camp['link']}</code>
                    <p class='camp-redirect'>Sẽ chuyển hướng đến: <a href='{$camp['target']}' target='_blank'>{$camp['target']}</a></p>
                </div>
                <code class='camp-link'>{$camp['link']}</code>
            </div>";
        }

        $basePath = $isPortal ? '/portal/affiliates' : '/affiliates';

        return "
        <div class='affiliate-portal-boltz'>
            <div class='aff-stats-grid-boltz'>
                <div class='stat-card-boltz'>
                    <span class='label'>Tháng này / Tổng</span>
                    <span class='value'>{$stats['commission_month']} / {$stats['commission_total']}</span>
                    <span class='tag-boltz'>Hoa hồng</span>
                </div>
                <div class='stat-card-boltz'>
                    <span class='label'>Tháng này / Tổng</span>
                    <span class='value'>{$stats['visits_month']} / {$stats['visits_total']}</span>
                    <span class='tag-boltz'>Truy cập</span>
                </div>
                <div class='stat-card-boltz'>
                    <span class='label'>Tháng này / Tổng</span>
                    <span class='value'>{$stats['registrations_month']} / {$stats['registrations_total']}</span>
                    <span class='tag-boltz'>Đăng ký</span>
                </div>
            </div>

            <div class='balance-banner-boltz'>
                <div class='bal-left'>
                    <span class='lbl'>Số dư có thể rút</span>
                    <h3 class='val'>{$stats['balance']} VND</h3>
                    <p>Mã cộng tác viên: <strong>#{$stats['affid']}</strong></p>
                </div>
                <div class='bal-right'>
                    <label>Link giới thiệu cá nhân</label>
                    <div class='link-input-boltz'>
                        <input type='text' value='https://optilarity.top/?affid={$stats['affid']}' readonly>
                        <button class='btn-withdraw-boltz'>Nạp tiền / Rút</button>
                    </div>
                </div>
            </div>

            <div class='aff-campaigns-boltz'>
                <h3 class='sec-title'>Campaign Links (Gợi ý)</h3>
                <div class='campaigns-list-boltz'>
                    {$campaignsHtml}
                </div>
            </div>
        </div>

        <style>
            .affiliate-portal-boltz { color: #1B2559; }
            .aff-stats-grid-boltz { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 30px; }
            .stat-card-boltz { background: #F4F7FE; padding: 25px; border-radius: 20px; border: 1px solid #E0E5F2; }
            .stat-card-boltz .label { font-size: 11px; color: #475467; text-transform: uppercase; font-weight: 700; margin-bottom: 5px; display: block; }
            .stat-card-boltz .value { font-size: 20px; font-weight: 800; color: #1B2559; display: block; }
            .stat-card-boltz .tag-boltz { font-size: 13px; color: #4318FF; font-weight: 700; margin-top: 10px; display: block; }

            .balance-banner-boltz { 
                background: white; 
                border: 1px solid #E0E5F2; 
                padding: 35px; 
                border-radius: 24px; 
                display: flex; 
                justify-content: space-between; 
                align-items: center; 
                margin-bottom: 40px;
                box-shadow: 0 10px 40px rgba(0,0,0,0.01);
            }
            .bal-left .lbl { color: #475467; font-size: 14px; font-weight: 600; }
            .bal-left .val { font-size: 32px; font-weight: 800; color: #1B2559; margin: 5px 0; }
            .bal-left p { margin: 0; color: #475467; font-size: 13px; font-weight: 500; }

            .bal-right { width: 45%; }
            .bal-right label { display: block; font-size: 13px; font-weight: 700; color: #1B2559; margin-bottom: 10px; }
            .link-input-boltz { display: flex; gap: 10px; }
            .link-input-boltz input { flex: 1; background: #F4F7FE; border: 1px solid #E0E5F2; padding: 12px 15px; border-radius: 12px; font-size: 13px; color: #4318FF; font-weight: 600; }
            .btn-withdraw-boltz { background: #4318FF; color: white; border: none; padding: 0 20px; border-radius: 12px; font-weight: 700; cursor: pointer; white-space: nowrap; transition: 0.3s; }
            .btn-withdraw-boltz:hover { background: #3311CC; }

            .sec-title { font-size: 18px; font-weight: 800; color: #1B2559; margin-bottom: 20px; }
            .campaigns-list-boltz { display: grid; grid-template-columns: 1fr; gap: 12px; }
            .aff-campaign-card { background: #fff; padding: 20px; border-radius: 16px; border: 1px solid #E0E5F2; display: flex; justify-content: space-between; align-items: center; }
            .aff-campaign-card h4 { margin: 0; font-size: 15px; font-weight: 700; color: #1B2559; }
            .camp-link { font-family: monospace; color: #008155; font-size: 12px; background: #E6FFF5; padding: 4px 10px; border-radius: 6px; font-weight: 600; }
            .camp-redirect { font-size: 12px; color: #475467; margin: 0; font-weight: 500; }
            .camp-redirect a { color: #4318FF; text-decoration: none; font-weight: 600; }
        </style>
        ";
    }

    protected function getCampaigns(string $affid): array
    {
        $baseUrl = "https://optilarity.top/?affid={$affid}&campaign=";
        $targets = [
            'wordpress-hosting' => ['name' => 'WordPress Hosting', 'target' => 'https://optilarity.top/wordpress-hosting'],
            'hosting-gia-re' => ['name' => 'Hosting giá rẻ', 'target' => 'https://optilarity.top/hosting-gia-re'],
            'free-hosting' => ['name' => 'Hosting miễn phí', 'target' => 'https://optilarity.top/hosting-mien-phi'],
            'dai-ly-hosting' => ['name' => 'Đại lý hosting', 'target' => 'https://optilarity.top/dai-ly-hosting'],
            'seo-hosting' => ['name' => 'SEO Hosting', 'target' => 'https://optilarity.top/seo-hosting'],
            'cloud-vps-gia-re' => ['name' => 'Cloud VPS giá rẻ', 'target' => 'https://optilarity.top/cloud-vps-gia-re'],
            'cloud-vps-pro' => ['name' => 'Cloud VPS Pro', 'target' => 'https://optilarity.top/cloud-vps-pro'],
            'domain' => ['name' => 'Đăng ký tên miền', 'target' => 'https://optilarity.top/ten-mien'],
            'dedicated-server' => ['name' => 'Thuê server riêng tại Việt Nam', 'target' => 'https://optilarity.top/thue-may-chu-server'],
            'ssl' => ['name' => 'Chứng chỉ SSL', 'target' => 'https://optilarity.top/ssl'],
            'email' => ['name' => 'Email doanh nghiệp', 'target' => 'https://optilarity.top/email-doanh-nghiep'],
            'nvme-hosting' => ['name' => 'MVMe Hosting', 'target' => 'https://optilarity.top/nvme-hosting'],
            'vps-n8n' => ['name' => 'VPS n8n', 'target' => 'https://optilarity.top/vps-n8n'],
        ];

        $campaigns = [];
        foreach ($targets as $slug => $info) {
            $campaigns[] = [
                'name' => $info['name'],
                'slug' => $slug,
                'link' => $baseUrl . $slug,
                'target' => $info['target']
            ];
        }

        return $campaigns;
    }

    public function commissions(): Response { return Response::html("Commissions Page"); }
    public function withdraw(): Response { return Response::html("Withdraw Page"); }
    public function vouchers(): Response { return Response::html("Vouchers Page"); }
    public function plans(): Response { return Response::html("Plans Page"); }
    
    public function track(Request $request, string $campaign): Response
    {
        // Simple tracking logic: Set a cookie and redirect
        $affid = $request->query('affid');
        
        // In a real app, log visit to DB here
        
        $campaignData = $this->getCampaigns($affid ?: '0');
        $target = 'https://optilarity.top/';
        
        foreach ($campaignData as $c) {
            if ($c['slug'] === $campaign) {
                $target = $c['target'];
                break;
            }
        }

        $response = Response::redirect($target);
        if ($affid) {
            // Set affiliate cookie for 30 days
            setcookie('optilarity_affid', (string)$affid, time() + (30 * 86400), '/');
        }
        
        return $response;
    }
}
