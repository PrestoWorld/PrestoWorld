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
            $active = str_starts_with($current, $link['url']) ? ' active' : '';
            $items .= "<a href=\"{$link['url']}\" class=\"presto-nav-item{$active}\">{$link['label']}</a>";
        }

        return "<nav class=\"presto-admin-nav\"><div class=\"presto-nav-brand\">⚡ Optilarity</div><div class=\"presto-nav-links\">{$items}</div></nav>";
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
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body.presto-admin { font-family: 'Inter', sans-serif; font-size: 14px; background: #f0f2f5; color: #1a1a2e; min-height: 100vh; }

        /* Nav */
        .presto-admin-nav { background: #0f172a; color: #e2e8f0; display: flex; align-items: center; padding: 0 24px; height: 56px; position: sticky; top: 0; z-index: 100; box-shadow: 0 2px 8px rgba(0,0,0,.3); gap: 32px; }
        .presto-nav-brand { font-size: 18px; font-weight: 700; color: #fff; white-space: nowrap; }
        .presto-nav-links { display: flex; align-items: center; gap: 4px; overflow-x: auto; }
        .presto-nav-item { color: #94a3b8; text-decoration: none; padding: 6px 12px; border-radius: 8px; font-size: 13px; font-weight: 500; white-space: nowrap; transition: background .15s, color .15s; }
        .presto-nav-item:hover, .presto-nav-item.active { background: rgba(99,102,241,.25); color: #c7d2fe; }

        /* Wrap / header */
        .presto-admin-wrap { max-width: 1400px; margin: 0 auto; padding: 24px 20px; }
        .presto-admin-header { margin-bottom: 24px; }
        .presto-breadcrumbs { font-size: 12px; color: #64748b; margin-bottom: 8px; }
        .presto-breadcrumbs a { color: #6366f1; text-decoration: none; }
        .presto-admin-title-bar { display: flex; align-items: center; gap: 16px; }
        .presto-admin-title { font-size: 24px; font-weight: 700; color: #0f172a; }

        /* Buttons */
        .presto-btn { display: inline-flex; align-items: center; gap: 6px; padding: 8px 18px; border-radius: 8px; font-size: 13px; font-weight: 600; border: 1px solid transparent; cursor: pointer; text-decoration: none; transition: all .15s; }
        .presto-btn-primary   { background: #6366f1; color: #fff; border-color: #6366f1; }
        .presto-btn-primary:hover { background: #4f46e5; }
        .presto-btn-secondary { background: #fff; color: #374151; border-color: #d1d5db; }
        .presto-btn-secondary:hover { background: #f3f4f6; }
        .presto-btn-danger    { background: #ef4444; color: #fff; border-color: #ef4444; }
        .presto-btn-ghost     { background: transparent; color: #6b7280; border-color: transparent; }

        /* Card */
        .presto-card { background: #fff; border-radius: 12px; border: 1px solid #e5e7eb; box-shadow: 0 1px 3px rgba(0,0,0,.06); margin-bottom: 20px; overflow: hidden; }
        .presto-card-header { padding: 16px 20px; border-bottom: 1px solid #f3f4f6; }
        .presto-card-title  { font-size: 15px; font-weight: 600; color: #111827; }
        .presto-card-body   { padding: 20px; }

        /* Table */
        .presto-table-wrap { background: #fff; border-radius: 12px; border: 1px solid #e5e7eb; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,.06); }
        .presto-list-table { width: 100%; border-collapse: collapse; font-size: 13px; }
        .presto-list-table thead { background: #f8fafc; }
        .presto-list-table th, .presto-list-table td { padding: 10px 14px; border-bottom: 1px solid #f1f5f9; text-align: left; vertical-align: middle; }
        .presto-list-table th { font-weight: 600; color: #374151; font-size: 12px; text-transform: uppercase; letter-spacing: .05em; }
        .presto-list-table tr:last-child td { border-bottom: none; }
        .presto-list-table tbody tr:hover { background: #f8fafc; }
        .presto-list-table .check-column { width: 40px; }
        .row-actions { font-size: 12px; color: #6b7280; margin-top: 4px; }
        .row-actions a { color: #6366f1; text-decoration: none; }
        .row-actions a:hover { text-decoration: underline; }

        /* Top bar / filters */
        .presto-table-topbar { display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px; flex-wrap: wrap; gap: 12px; }
        .presto-subsubsub { display: flex; list-style: none; gap: 0; font-size: 13px; }
        .presto-subsubsub li a { color: #6366f1; text-decoration: none; padding: 4px 8px; border-radius: 6px; }
        .presto-subsubsub li a.current, .presto-subsubsub li a:hover { background: #eef2ff; }
        .presto-search-box { display: flex; gap: 8px; }
        .presto-search-box input { padding: 7px 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 13px; min-width: 220px; outline: none; }
        .presto-search-box input:focus { border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99,102,241,.1); }

        /* Tablenav */
        .tablenav { display: flex; align-items: center; justify-content: space-between; padding: 10px 0; flex-wrap: wrap; gap: 10px; }
        .tablenav-pages { display: flex; align-items: center; gap: 8px; font-size: 13px; color: #6b7280; }
        .tablenav-pages .button { padding: 4px 10px; border: 1px solid #d1d5db; border-radius: 6px; text-decoration: none; color: #374151; font-size: 12px; }
        .tablenav-pages .button:hover { background: #f3f4f6; }
        .alignleft.actions { display: flex; gap: 8px; align-items: center; }
        .presto-select { padding: 6px 10px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 13px; }

        /* Badge */
        .badge { display: inline-flex; align-items: center; padding: 2px 10px; border-radius: 100px; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: .04em; }
        .badge-active     { background: #d1fae5; color: #065f46; }
        .badge-pending    { background: #fef9c3; color: #854d0e; }
        .badge-completed  { background: #dbeafe; color: #1e40af; }
        .badge-cancelled  { background: #fee2e2; color: #991b1b; }
        .badge-expired    { background: #f3f4f6; color: #6b7280; }
        .badge-paid       { background: #d1fae5; color: #065f46; }
        .badge-failed     { background: #fee2e2; color: #991b1b; }
        .badge-draft      { background: #f3f4f6; color: #6b7280; }
        .badge-sent       { background: #dbeafe; color: #1e40af; }
        .badge-overdue    { background: #fee2e2; color: #991b1b; }
        .badge-suspended  { background: #fef3c7; color: #92400e; }
        .badge-revoked    { background: #fee2e2; color: #991b1b; }
        .badge-trialing   { background: #f0fdf4; color: #15803d; }
        .badge-software   { background: #ede9fe; color: #5b21b6; }
        .badge-plugin     { background: #dbeafe; color: #1e3a8a; }
        .badge-theme      { background: #fce7f3; color: #9d174d; }
        .badge-deprecated { background: #f3f4f6; color: #6b7280; }
        .badge-archived   { background: #f3f4f6; color: #6b7280; }

        /* Form */
        .presto-form { display: flex; flex-direction: column; gap: 0; }
        .presto-field-group { margin-bottom: 20px; }
        .presto-field-label { display: block; font-weight: 500; font-size: 13px; color: #374151; margin-bottom: 6px; }
        .presto-input { width: 100%; padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px; font-family: inherit; outline: none; transition: border-color .15s, box-shadow .15s; background: #fff; }
        .presto-input:focus { border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99,102,241,.1); }
        .presto-textarea { resize: vertical; min-height: 100px; }
        .presto-checkbox-label { display: flex; align-items: center; gap: 8px; font-size: 14px; cursor: pointer; }
        .presto-field-hint { font-size: 12px; color: #6b7280; margin-top: 4px; }
        .presto-submit-bar { display: flex; gap: 12px; align-items: center; justify-content: flex-end; padding-top: 16px; border-top: 1px solid #f3f4f6; margin-top: 8px; }

        /* Notice */
        .presto-notice { padding: 12px 16px; border-radius: 8px; margin-bottom: 16px; font-size: 13px; font-weight: 500; }
        .presto-notice-success { background: #d1fae5; color: #065f46; border: 1px solid #a7f3d0; }
        .presto-notice-error   { background: #fee2e2; color: #991b1b; border: 1px solid #fca5a5; }
        .presto-notice-warning { background: #fef3c7; color: #92400e; border: 1px solid #fde68a; }
        .presto-notice-info    { background: #dbeafe; color: #1e40af; border: 1px solid #93c5fd; }

        /* Stat grid (dashboard-style cards) */
        .presto-stat-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 16px; margin-bottom: 24px; }
        .presto-stat-card { background:#fff; border-radius:12px; border:1px solid #e5e7eb; padding:20px; text-align:center; }
        .presto-stat-value { font-size: 32px; font-weight: 800; color: #0f172a; }
        .presto-stat-label { font-size: 12px; color: #64748b; margin-top: 4px; text-transform: uppercase; letter-spacing: .05em; }
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
