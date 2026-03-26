<?php

namespace Modules\Dashboard\Controllers;

use App\Foundation\Admin\AdminController;
use Witals\Framework\Http\Response;

class DashboardController extends AdminController
{
    public function index()
    {
        // 1. Register Widgets (Hooks-based)
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
            <div class="presto-card" style="margin-top: 1rem; padding: 5rem 2rem; text-align: center; background: rgba(255,255,255,0.015); border: 1px dashed rgba(255,255,255,0.08); border-radius: 40px;">
                <img src="https://ui-avatars.com/api/?name=PW&background=6366f1&color=fff&size=128" style="width: 80px; height: 80px; border-radius: 24px; margin-bottom: 2rem; box-shadow: 0 20px 40px rgba(99, 102, 241, 0.3);">
                <h2 style="font-size: 2.2rem; font-weight: 800; letter-spacing: -0.04em; margin-bottom: 1rem;">Welcome to PrestoWorld</h2>
                <p style="color: var(--text-muted); max-width: 580px; margin: 0 auto 3rem; font-size: 1.15rem; line-height: 1.7; opacity: 0.8;">
                    The system core is activated. You can now start building your custom experience by registering adaptive widgets.
                </p>
                <div style="display: flex; gap: 1.5rem; justify-content: center; align-items: center;">
                    <a href="https://prestoworld.com/docs" class="presto-btn presto-btn-primary">Explore Documentation</a>
                    <a href="/" class="presto-btn presto-btn-secondary">Visit Frontend</a>
                </div>
            </div>';
        }

        return $this->adminPage('System Dashboard', $widgetsHtml);
    }
}
