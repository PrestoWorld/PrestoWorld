<?php

namespace Modules\Dashboard\Controllers;

use App\Foundation\Admin\AdminController;
use Witals\Framework\Http\Response;

class DashboardController extends AdminController
{
    public function index()
    {
        // 1. Define default system-level stats
        $defaultStats = [
            [
                'label' => 'Active Components',
                'value' => '42',
                'trend' => 'System up to date',
                'icon'  => '🧩',
                'color' => '#6366f1'
            ],
            [
                'label' => 'Core Modules',
                'value' => '12',
                'trend' => 'All stable',
                'icon'  => '📦',
                'color' => '#10b981'
            ],
            [
                'label' => 'Memory Usage',
                'value' => '24MB',
                'trend' => 'Peak: 31MB',
                'icon'  => '⚡',
                'color' => '#f59e0b'
            ],
            [
                'label' => 'System Uptime',
                'value' => '99.9%',
                'trend' => 'Healthy',
                'icon'  => '🛡️',
                'color' => '#ef4444'
            ]
        ];

        // 2. Allow modules to filter/modify stats
        $stats = apply_filters('dashboard.stats', $defaultStats);

        // Render stats HTML
        $statsHtml = '<div class="presto-stats-row">';
        foreach ($stats as $s) {
            $statsHtml .= "
            <div class=\"presto-card stat-card-premium\" style=\"--accent: {$s['color']}\">
                <div class=\"stat-info\">
                    <span class=\"stat-label\">{$s['label']}</span>
                    <h3 class=\"stat-value\">{$s['value']}</h3>
                    <span class=\"stat-trend\">{$s['trend']}</span>
                </div>
                <div class=\"stat-visual\">
                    <div class=\"stat-icon-wrap\"><span class=\"stat-icon\">{$s['icon']}</span></div>
                </div>
            </div>";
        }
        $statsHtml .= '</div>';

        // 3. Register Widgets (Hooks-based)
        $widgetsHtml = '';
        $registry = app(\PrestoWorld\Context\ContextRegistry::class);
        $context = $registry->get('dashboard.widgets');
        
        do_action('dashboard.init_widgets', $context);

        if (!$context->isEmpty()) {
            foreach ($context->resolve() as $item) {
                $w = $item->resolve();
                // Special handling for WidgetContexts
                $widgetsHtml .= "
                <div class=\"presto-card stat-card-premium\" style=\"--accent: #6366f1\">
                    <div class=\"stat-info\">
                        <span class=\"stat-label\">{$w['label']}</span>
                        <h3 class=\"stat-value\">{$w['value']}</h3>
                        <span class=\"stat-trend\">{$w['trend']}</span>
                    </div>
                    <div class=\"stat-visual\">
                        <div class=\"stat-icon-wrap\"><span class=\"stat-icon\">{$w['icon']}</span></div>
                    </div>
                </div>";
            }
        } else {
            // Generic Empty State / Quick Start
            $widgetsHtml = '
            <div class="presto-card" style="margin-top: 2rem; padding: 4rem; text-align: center; background: rgba(255,255,255,0.02); border: 1px dashed rgba(255,255,255,0.1);">
                <div style="font-size: 4rem; margin-bottom: 1.5rem;">🌌</div>
                <h2 style="font-size: 1.75rem; font-weight: 700;">Welcome to PrestoWorld</h2>
                <p style="color: var(--text-muted); max-width: 600px; margin: 0.5rem auto 2.5rem; font-size: 1.1rem; line-height: 1.6;">
                    The system is ready. You can now start building your custom dashboard by registering widgets via the <code>dashboard.init_widgets</code> hook.
                </p>
                <div style="display: flex; gap: 1.25rem; justify-content: center;">
                    <a href="https://prestoworld.com/docs" class="btn-primary" style="padding: 0.8rem 2rem;">Read Documentation</a>
                    <a href="/" class="btn-ghost" style="padding: 0.8rem 2rem;">Visit Site</a>
                </div>
            </div>';
        }

        return $this->adminPage('System Dashboard', $statsHtml . $widgetsHtml);
    }
}
