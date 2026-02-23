<?php

declare(strict_types=1);

namespace App\Foundation\Admin;

use Witals\Framework\Http\Request;
use Witals\Framework\Http\Response;

/**
 * Base Admin Controller
 *
 * Provides common helpers for all module admin controllers:
 *   - HTML page scaffold (header, nav, footer)
 *   - Form field renderers (input, select, textarea, checkbox…)
 *   - Flash / notice rendering
 *   - JSON response helper
 *   - Redirect helper
 *
 * Every module admin controller extends this class.
 */
abstract class AdminController
{
    protected mixed $app;

    public function __construct(mixed $app)
    {
        $this->app = $app;
    }

    // =========================================================================
    // Page scaffold
    // =========================================================================

    /**
     * Wrap content in the shared admin page chrome.
     *
     * @param string $title    Page/section title
     * @param string $content  Body HTML
     * @param array  $options  ['new_url' => '/dashboard/x/create', 'breadcrumbs' => [...]]
     */
    protected function adminPage(string $title, string $content, array $options = []): string
    {
        $newUrl      = $options['new_url'] ?? '';
        $newLabel    = $options['new_label'] ?? 'Add New';
        $breadcrumbs = $options['breadcrumbs'] ?? [];

        $addBtn  = $newUrl
            ? "<a href=\"{$newUrl}\" class=\"presto-btn presto-btn-primary\">{$newLabel}</a>"
            : '';

        $breadcrumbHtml = '';
        if (!empty($breadcrumbs)) {
            $parts = [];
            foreach ($breadcrumbs as $label => $url) {
                $parts[] = $url ? "<a href=\"{$url}\">{$label}</a>" : "<span>{$label}</span>";
            }
            $breadcrumbHtml = '<nav class="presto-breadcrumbs">' . implode(' › ', $parts) . '</nav>';
        }

        return <<<HTML
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>{$title} — Optilarity Admin</title>
            <style>
                {$this->adminCss()}
            </style>
        </head>
        <body class="presto-admin">
            {$this->adminNav()}
            <div class="presto-admin-wrap">
                <div class="presto-admin-header">
                    {$breadcrumbHtml}
                    <div class="presto-admin-title-bar">
                        <h1 class="presto-admin-title">{$title}</h1>
                        {$addBtn}
                    </div>
                </div>
                <div class="presto-admin-content">
                    {$content}
                </div>
            </div>
            <script>{$this->adminJs()}</script>
        </body>
        </html>
        HTML;
    }

    protected function adminNav(): string
    {
        $links = [
            ['label' => '🏠 Dashboard',       'url' => '/dashboard'],
            ['label' => '👥 Customers',        'url' => '/dashboard/customers'],
            ['label' => '📦 Orders',           'url' => '/dashboard/orders'],
            ['label' => '🧾 Invoices',         'url' => '/dashboard/invoices'],
            ['label' => '🔑 Licenses',         'url' => '/dashboard/licenses'],
            ['label' => '💎 Memberships',      'url' => '/dashboard/memberships'],
            ['label' => '🛍️ Catalog',          'url' => '/dashboard/catalog'],
            ['label' => '🪝 Webhooks',         'url' => '/dashboard/webhooks'],
            ['label' => '👤 Profile',          'url' => '/dashboard/profile'],
        ];

        $current = $_SERVER['REQUEST_URI'] ?? '';
        $items   = '';
        foreach ($links as $link) {
            $active = ($current === $link['url'] || str_starts_with($current, $link['url'] . '/')) ? ' active' : '';
            $items .= "<a href=\"{$link['url']}\" class=\"presto-nav-item{$active}\">{$link['label']}</a>";
        }

        return <<<HTML
        <nav class="presto-admin-nav">
            <div class="presto-nav-brand">Digital<span>Core.</span></div>
            <div class="presto-nav-links">{$items}</div>
            <div class="presto-nav-user">
                <div class="user-avatar" style="background: linear-gradient(135deg, #6366f1, #a855f7);">AD</div>
                <div class="user-info">
                    <span class="user-name">Alexander Dev</span>
                    <span class="user-role">Super Admin</span>
                </div>
            </div>
        </nav>
HTML;
    }

    // =========================================================================
    // Notices
    // =========================================================================

    protected function notice(string $message, string $type = 'info'): string
    {
        $icons = ['success' => '✅', 'error' => '❌', 'warning' => '⚠️', 'info' => 'ℹ️'];
        $icon  = $icons[$type] ?? 'ℹ️';
        return "<div class=\"presto-notice presto-notice-{$type}\">{$icon} {$message}</div>";
    }

    // =========================================================================
    // Form field helpers
    // =========================================================================

    protected function formOpen(string $action, string $method = 'POST', string $id = ''): string
    {
        $idAttr = $id ? " id=\"{$id}\"" : '';
        // PUT/PATCH/DELETE need _method override for HTML forms
        $realMethod = strtoupper($method);
        $formMethod = in_array($realMethod, ['GET', 'POST'], true) ? $realMethod : 'POST';
        $methodField = !in_array($realMethod, ['GET', 'POST'], true)
            ? "<input type=\"hidden\" name=\"_method\" value=\"{$realMethod}\">"
            : '';
        return "<form action=\"{$action}\" method=\"{$formMethod}\"{$idAttr} class=\"presto-form\">{$methodField}";
    }

    protected function formClose(): string
    {
        return '</form>';
    }

    protected function fieldGroup(string $label, string $input, string $hint = ''): string
    {
        $hintHtml = $hint ? "<p class=\"presto-field-hint\">{$hint}</p>" : '';
        return <<<HTML
        <div class="presto-field-group">
            <label class="presto-field-label">{$label}</label>
            <div class="presto-field-control">{$input}{$hintHtml}</div>
        </div>
        HTML;
    }

    protected function input(
        string $name,
        string $type = 'text',
        mixed  $value = '',
        string $placeholder = '',
        bool   $required = false
    ): string {
        $req   = $required ? ' required' : '';
        $ph    = $placeholder ? " placeholder=\"{$placeholder}\"" : '';
        $val   = htmlspecialchars((string)$value, ENT_QUOTES);
        return "<input type=\"{$type}\" name=\"{$name}\" id=\"field-{$name}\" value=\"{$val}\" class=\"presto-input\"{$ph}{$req}>";
    }

    protected function textarea(string $name, mixed $value = '', string $placeholder = '', int $rows = 4): string
    {
        $ph  = $placeholder ? " placeholder=\"{$placeholder}\"" : '';
        $val = htmlspecialchars((string)$value, ENT_QUOTES);
        return "<textarea name=\"{$name}\" id=\"field-{$name}\" rows=\"{$rows}\" class=\"presto-input presto-textarea\"{$ph}>{$val}</textarea>";
    }

    /**
     * @param array<string, string> $options  [value => label]
     */
    protected function select(string $name, array $options, mixed $selected = '', bool $required = false): string
    {
        $req  = $required ? ' required' : '';
        $html = "<select name=\"{$name}\" id=\"field-{$name}\" class=\"presto-select\"{$req}>";
        foreach ($options as $val => $label) {
            $sel   = (string)$val === (string)$selected ? ' selected' : '';
            $html .= "<option value=\"{$val}\"{$sel}>{$label}</option>";
        }
        $html .= '</select>';
        return $html;
    }

    protected function checkbox(string $name, bool $checked = false, string $label = ''): string
    {
        $chk  = $checked ? ' checked' : '';
        $html = "<label class=\"presto-checkbox-label\">"
              . "<input type=\"checkbox\" name=\"{$name}\" id=\"field-{$name}\" value=\"1\"{$chk} class=\"presto-checkbox\">"
              . " {$label}</label>";
        return $html;
    }

    protected function submitBar(string $label = 'Save', string $cancelUrl = ''): string
    {
        $cancel = $cancelUrl
            ? "<a href=\"{$cancelUrl}\" class=\"presto-btn presto-btn-ghost\">Cancel</a>"
            : '';
        return "<div class=\"presto-submit-bar\">{$cancel}<button type=\"submit\" class=\"presto-btn presto-btn-primary\">{$label}</button></div>";
    }

    /**
     * Render a full presto-card-wrapped form section.
     */
    protected function formCard(string $title, string $fieldsHtml): string
    {
        return <<<HTML
        <div class="presto-card">
            <div class="presto-card-header"><h2 class="presto-card-title">{$title}</h2></div>
            <div class="presto-card-body">{$fieldsHtml}</div>
        </div>
        HTML;
    }

    // =========================================================================
    // Response helpers
    // =========================================================================

    protected function htmlResponse(string $html, int $status = 200): Response
    {
        return Response::html($html, $status);
    }

    protected function jsonResponse(mixed $data, int $status = 200): Response
    {
        return Response::json($data, $status);
    }

    protected function redirect(string $url, int $status = 302): Response
    {
        return new Response($status, ['Location' => $url], '');
    }

    // =========================================================================
    // DB helpers
    // =========================================================================

    protected function db(): \Cycle\Database\DatabaseInterface
    {
        return $this->app->make(\Cycle\Database\DatabaseInterface::class);
    }

    // =========================================================================
    // Inline CSS + JS (shared admin DesignSystem)
    // =========================================================================

    protected function adminCss(): string
    {
        return <<<CSS
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');
        :root {
            --bg-body: #0a0c10;
            --bg-card: #12151c;
            --bg-nav: #0d0f14;
            --border: rgba(255,255,255,0.08);
            --text-main: #f8fafc;
            --text-dim: #94a3b8;
            --primary: #6366f1;
            --primary-light: #818cf8;
            --success: #10b981;
            --danger: #ef4444;
            --warning: #f59e0b;
        }
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body.presto-admin { font-family: 'Inter', sans-serif; font-size: 14px; background: var(--bg-body); color: var(--text-main); min-height: 100vh; }

        /* Nav */
        .presto-admin-nav { background: var(--bg-nav); color: var(--text-main); display: flex; align-items: center; padding: 0 32px; height: 64px; position: sticky; top: 0; z-index: 100; border-bottom: 1px solid var(--border); gap: 40px; }
        .presto-nav-brand { font-size: 20px; font-weight: 800; color: #fff; white-space: nowrap; }
        .presto-nav-brand span { color: var(--primary); }
        .presto-nav-links { display: flex; align-items: center; gap: 4px; flex: 1; overflow-x: auto; }
        .presto-nav-item { color: var(--text-dim); text-decoration: none; padding: 8px 16px; border-radius: 10px; font-size: 13px; font-weight: 500; white-space: nowrap; transition: 0.2s; }
        .presto-nav-item:hover, .presto-nav-item.active { background: rgba(99,102,241,0.12); color: var(--primary-light); }
        .presto-nav-user { display: flex; align-items: center; gap: 12px; padding-left: 20px; border-left: 1px solid var(--border); }
        .user-avatar { width: 36px; height: 36px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 12px; color: #fff; }
        .user-info { display: flex; flex-direction: column; }
        .user-name { font-size: 13px; font-weight: 600; }
        .user-role { font-size: 11px; color: var(--text-dim); }

        /* Wrap / header */
        .presto-admin-wrap { max-width: 1600px; margin: 0 auto; padding: 32px 40px; }
        .presto-admin-header { margin-bottom: 32px; }
        .presto-breadcrumbs { font-size: 12px; color: var(--text-dim); margin-bottom: 12px; }
        .presto-breadcrumbs a { color: var(--primary); text-decoration: none; }
        .presto-admin-title-bar { display: flex; align-items: center; justify-content: space-between; gap: 20px; }
        .presto-admin-title { font-size: 28px; font-weight: 800; letter-spacing: -0.02em; }
        .section-title { font-size: 18px; font-weight: 700; margin: 40px 0 20px; color: var(--text-main); display: flex; align-items: center; gap: 10px; }

        /* Buttons */
        .presto-btn { display: inline-flex; align-items: center; gap: 8px; padding: 10px 20px; border-radius: 12px; font-size: 13px; font-weight: 600; border: 1px solid transparent; cursor: pointer; text-decoration: none; transition: 0.2s; }
        .presto-btn-primary { background: var(--primary); color: #fff; }
        .presto-btn-primary:hover { background: #4f46e5; transform: translateY(-1px); }
        .presto-btn-secondary { background: rgba(255,255,255,0.05); color: var(--text-main); border-color: var(--border); }
        .presto-btn-secondary:hover { background: rgba(255,255,255,0.1); }
        .btn-ghost-sm { background: none; border: none; color: var(--text-dim); font-size: 12px; font-weight: 500; cursor: pointer; transition: 0.2s; padding: 4px 0; }
        .btn-ghost-sm:hover { color: var(--primary-light); }

        /* Card */
        .presto-card { background: var(--bg-card); border-radius: 20px; border: 1px solid var(--border); box-shadow: 0 4px 6px rgba(0,0,0,0.2); margin-bottom: 24px; overflow: hidden; }
        .presto-card-header { padding: 20px 24px; border-bottom: 1px solid var(--border); display: flex; align-items: center; justify-content: space-between; }
        .presto-card-title { font-size: 16px; font-weight: 700; }
        .presto-card-body { padding: 24px; }
        .p-0 { padding: 0 !important; }

        /* Premium Stat Card */
        .presto-dashboard-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 24px; }
        .stat-card-premium { display: flex; align-items: center; justify-content: space-between; padding: 24px; }
        .stat-main { display: flex; flex-direction: column; }
        .stat-label { font-size: 13px; color: var(--text-dim); font-weight: 500; margin-bottom: 8px; }
        .stat-value { font-size: 32px; font-weight: 800; line-height: 1; margin-bottom: 8px; }
        .stat-trend { font-size: 12px; font-weight: 600; }
        .trend-up { color: var(--success); }
        .stat-icon-wrap { width: 48px; height: 48px; border-radius: 12px; background: rgba(255,255,255,0.05); display: flex; align-items: center; justify-content: center; font-size: 20px; }
        .stat-card-premium.danger { border-left: 4px solid var(--danger); }

        /* Category Card */
        .presto-category-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 24px; }
        .category-card .cat-header { display: flex; align-items: center; gap: 16px; margin-bottom: 20px; }
        .cat-icon { width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 24px; }
        .cat-title-group h3 { font-size: 16px; font-weight: 700; margin-bottom: 4px; }
        .cat-stats { display: flex; flex-direction: column; gap: 8px; margin-bottom: 20px; }
        .cat-stat { display: flex; justify-content: space-between; font-size: 13px; color: var(--text-dim); }
        .cat-stat strong { color: var(--text-main); }
        .cat-progress { margin-top: 16px; }
        .progress-label { display: flex; justify-content: space-between; font-size: 11px; font-weight: 600; text-transform: uppercase; margin-bottom: 6px; color: var(--text-dim); }
        .progress-bar { height: 6px; background: rgba(255,255,255,0.05); border-radius: 3px; overflow: hidden; }
        .progress-fill { height: 100%; background: var(--primary); border-radius: 3px; }
        .cat-footer { margin-top: 16px; padding-top: 16px; border-top: 1px solid var(--border); }

        /* List Table */
        .presto-list-table { width: 100%; border-collapse: collapse; font-size: 13px; }
        .presto-list-table th { text-align: left; padding: 16px 24px; font-size: 11px; font-weight: 700; text-transform: uppercase; color: var(--text-dim); border-bottom: 1px solid var(--border); letter-spacing: 0.05em; }
        .presto-list-table td { padding: 16px 24px; border-bottom: 1px solid var(--border); color: var(--text-main); }
        .presto-list-table tr:hover { background: rgba(255,255,255,0.02); }
        code { background: rgba(0,0,0,0.3); padding: 2px 6px; border-radius: 4px; font-family: monospace; color: var(--primary-light); }

        /* Badge Custom */
        .badge { display: inline-flex; align-items: center; padding: 4px 10px; border-radius: 8px; font-size: 11px; font-weight: 700; text-transform: uppercase; }
        .badge-active { background: rgba(16,185,129,0.15); color: var(--success); }
        .badge-software { background: rgba(99,102,241,0.15); color: var(--primary-light); }
        .badge-plugin { background: rgba(59,130,246,0.15); color: #60a5fa; }
        .badge-membership { background: rgba(245,158,11,0.15); color: var(--warning); }

        /* Dashboard Bottom Layout */
        .dashboard-bottom-row { display: grid; grid-template-columns: 2fr 1fr; gap: 24px; margin-top: 24px; }
        .card-tabs { display: flex; gap: 8px; }
        .tab-btn { background: rgba(255,255,255,0.05); border: none; color: var(--text-dim); padding: 6px 12px; border-radius: 8px; font-size: 12px; font-weight: 600; cursor: pointer; }
        .tab-btn.active { background: var(--primary); color: #fff; }

        /* Donut Mock */
        .donut-chart-mock { width: 150px; height: 150px; border-radius: 50%; border: 15px solid var(--primary); margin: 20px auto; position: relative; display: flex; align-items: center; justify-content: center; border-left-color: var(--success); border-bottom-color: var(--warning); border-right-color: #ec4899; }
        .donut-inner { font-size: 20px; font-weight: 800; }
        .chart-legend { list-style: none; margin-top: 20px; display: flex; flex-direction: column; gap: 10px; }
        .chart-legend li { display: flex; align-items: center; justify-content: space-between; font-size: 13px; color: var(--text-dim); }
        .dot { width: 8px; height: 8px; border-radius: 50%; margin-right: 10px; display: inline-block; }
        
        /* Form Overrides for Dark Theme */
        .presto-input, .presto-select { background: rgba(0,0,0,0.2); border-color: var(--border); color: var(--text-main); }
        .presto-input:focus { border-color: var(--primary); background: rgba(0,0,0,0.3); }
        .presto-field-label { color: var(--text-main); }
        CSS;
        CSS;
    }

    protected function adminJs(): string
    {
        return <<<JS
        // Bulk action handler
        document.querySelectorAll('.presto-bulk-apply').forEach(btn => {
            btn.addEventListener('click', () => {
                const position = btn.dataset.position;
                const select   = document.querySelector('#bulk-action-selector-' + position);
                const action   = select ? select.value : '-1';
                if (action === '-1') { alert('Please select a bulk action.'); return; }
                const checked  = [...document.querySelectorAll('input[name="item[]"]:checked')].map(c => c.value);
                if (!checked.length) { alert('Please select at least one item.'); return; }
                const confirm  = select.options[select.selectedIndex].dataset.confirm;
                if (confirm && !window.confirm(confirm)) return;
                fetch('/api/' + window.location.pathname.split('/')[2], {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json', 'X-Bulk-Action': action},
                    body: JSON.stringify({ids: checked, action})
                }).then(r => r.json()).then(d => { if (d.success) location.reload(); else alert(d.message || 'Error'); });
            });
        });

        // Select-all checkbox
        const selectAll = document.getElementById('cb-select-all-1');
        if (selectAll) {
            selectAll.addEventListener('change', () => {
                document.querySelectorAll('input[name="item[]"]').forEach(c => c.checked = selectAll.checked);
            });
        }

        // Non-GET row actions
        document.querySelectorAll('a[data-method]').forEach(a => {
            a.addEventListener('click', e => {
                e.preventDefault();
                const method = a.dataset.method;
                const url    = a.dataset.url;
                fetch(url, { method, headers: {'Content-Type':'application/json'} })
                    .then(r => r.json())
                    .then(d => { if (d.success !== false) location.reload(); else alert(d.message || 'Error'); });
            });
        });
        JS;
    }
}
