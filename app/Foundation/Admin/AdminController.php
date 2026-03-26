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
    protected \Witals\Framework\Support\AssetManager $assets;

    public function __construct(\Witals\Framework\Application $app)
    {
        $this->app = $app;
        $this->assets = $app->make(\Witals\Framework\Support\AssetManager::class);
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

        // Configure assets for Admin (using advanced AssetManager)
        $this->assets->setContext('admin');
        
        // admin-dashboard depends on admin-core.css
        $this->assets->enqueueCss('admin-core', 'css/admin-core.css');
        $this->assets->enqueueCss('admin-dashboard', '/css/admin-dashboard.css', ['presto-ui']);
        $this->assets->enqueueCss('marketplace', '/css/marketplace.css', ['admin-dashboard']);

        // JS assets
        $this->assets->enqueueJs('admin-core', 'js/admin-solid-core.js', [], ['defer' => true, 'type' => 'module']);

        // Mark as rendered before calling engine
        $this->app->instance('view.rendered', true);
        
        $viewFactory = $this->app->make(\Witals\Framework\Contracts\View\Factory::class);
        return $viewFactory->make('admin/layout', [
            'title' => $title,
            'content' => $content,
            'add_btn' => $addBtn,
            'breadcrumb_html' => $breadcrumbHtml,
            'assets_css' => $this->assets->renderCss(),
            'assets_js' => $this->assets->renderJs(),
            'inline_css' => $this->adminCss(),
            'inline_js' => $this->adminJs(),
            'nav_html' => $this->adminNav(),
        ])->render();
    }

    protected function adminNav(): string
    {
        $current = $_SERVER['REQUEST_URI'] ?? '';
        $html = '<div class="presto-nav-groups">';
        
        $groups = app('contexts')->resolve('dashboard.menu');
        
        // Normalize groups to ensure all required keys exist
        $normalizedGroups = array_map(function ($item) {
            $children = $item['children'] ?? [];
            $subGroups = $item['groups'] ?? [];
            $groupItems = $item['items'] ?? [];
            
            // Avoid duplication if they are same items
            $rawChildren = array_merge($children, $subGroups, $groupItems);
            $uniqueChildren = [];
            foreach ($rawChildren as $child) {
                $uniqueChildren[$child['id'] ?? uniqid()] = $child;
            }
            
            $item['children'] = array_values($uniqueChildren);
            $item['groups'] = [];
            $item['url'] = $item['url'] ?? '#';
            $item['label'] = $item['label'] ?? ($item['title'] ?? 'Untitled');
            $item['icon'] = $item['icon'] ?? '📁';
            
            return $item;
        }, $groups);

        // Pre-check for any active child to handle accordion opening
        $anySubmenuActive = false;
        foreach ($normalizedGroups as $data) {
            if (!empty($data['children'])) {
                foreach ($data['children'] as $child) {
                    $childUrl = $child['url'] ?? '#';
                    if ($current === $childUrl || str_starts_with($current, $childUrl . '?')) {
                        $anySubmenuActive = true;
                        break 2;
                    }
                }
            }
        }

        ob_start();
        do_action('admin.nav.before');
        $html .= ob_get_clean();
        
        foreach ($normalizedGroups as $index => $data) {
            // Case 1: Simple Link
            if (empty($data['children'])) {
                $active = ($current === $data['url'] || str_starts_with($current, $data['url'] . '?')) ? ' active' : '';
                $html .= "<a href=\"{$data['url']}\" class=\"presto-nav-item{$active}\">";
                $html .= "  <span class='nav-icon'>{$data['icon']}</span> <span class='nav-label'>{$data['label']}</span>";
                $html .= "</a>";
                continue;
            }

            // Case 2: Group with Children
            $allChildren = $data['children'];
            
            $hasActiveChild = false;
            foreach ($allChildren as $child) {
                $childUrl = $child['url'] ?? '#';
                if ($current === $childUrl || str_starts_with($current, $childUrl . '?')) {
                    $hasActiveChild = true;
                    break;
                }
            }

            // Default to open first group if no other submenu is active
            $shouldOpen = $hasActiveChild || (!$anySubmenuActive && $index === 0);
            $openClass = $shouldOpen ? ' is-open' : '';
            $activeParentClass = $hasActiveChild ? ' active-parent' : '';
            
            $html .= "<div class=\"presto-nav-group-wrapper{$openClass}\">";
            $html .= "  <div class=\"presto-nav-item has-children{$activeParentClass}\" data-toggle=\"submenu\">";
            $html .= "      <span class='nav-icon'>{$data['icon']}</span>";
            $html .= "      <span class='nav-label'>{$data['label']}</span>";
            $html .= "      <span class='nav-chevron'><svg width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='3' stroke-linecap='round' stroke-linejoin='round'><path d='M6 9l6 6 6-6'/></svg></span>";
            $html .= "  </div>";
            $html .= "  <div class=\"presto-submenu\">";
            
            foreach ($allChildren as $child) {
                $childUrl = $child['url'] ?? '#';
                $childLabel = $child['label'] ?? ($child['title'] ?? 'Untitled');
                $childIcon = $child['icon'] ?? '';
                $childActive = ($current === $childUrl || str_starts_with($current, $childUrl . '?')) ? ' active' : '';
                $html .= "      <a href=\"{$childUrl}\" class=\"presto-submenu-item{$childActive}\">";
                $html .= "          <span class='sub-icon'>{$childIcon}</span> <span class='sub-label'>{$childLabel}</span>";
                $html .= "      </a>";
            }
            
            $html .= "  </div>";
            $html .= "</div>";
        }

        ob_start();
        do_action('admin.nav.after');
        $html .= ob_get_clean();
        
        $html .= '</div>';
        return $html;
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
        return "<input type=\"{$type}\" name=\"{$name}\" id=\"field-{$name}\" value=\"{$val}\" class=\"presto-input\" autocomplete=\"off\"{$ph}{$req}>";
    }

    protected function textarea(string $name, mixed $value = '', string $placeholder = '', int $rows = 4): string
    {
        $ph  = $placeholder ? " placeholder=\"{$placeholder}\"" : '';
        $val = htmlspecialchars((string)$value, ENT_QUOTES);
        return "<textarea name=\"{$name}\" id=\"field-{$name}\" rows=\"{$rows}\" class=\"presto-input presto-textarea\"{$ph}>{$val}</textarea>";
    }

    protected function select(string $name, array $options, mixed $selected = '', bool $searchable = false, string $placeholder = 'Search...'): string
    {
        if ($searchable) {
            $formattedOptions = [];
            foreach ($options as $val => $lbl) {
                $formattedOptions[] = ['value' => (string)$val, 'label' => (string)$lbl];
            }
            return $this->searchableSelect($name, $formattedOptions, $selected, $placeholder);
        }

        $html = "<select name=\"{$name}\" id=\"field-{$name}\" class=\"presto-select\">";
        foreach ($options as $val => $label) {
            $sel   = (string)$val === (string)$selected ? ' selected' : '';
            $html .= "<option value=\"{$val}\"{$sel}>{$label}</option>";
        }
        $html .= '</select>';
        return $html;
    }

    protected function searchableSelect(string $name, array $options, mixed $value = '', string $placeholder = 'Search...'): string
    {
        $jsonOptions = json_encode($options);
        return <<<HTML
        <div data-solid-component="ComboBox" data-config='{"name":"{$name}", "options":{$jsonOptions}, "value":"{$value}", "placeholder":"{$placeholder}"}'></div>
HTML;
    }

    protected function searchableFieldGroup(string $label, string $name, array $options, mixed $value = '', string $placeholder = 'Search...'): string
    {
        return <<<HTML
        <div class="presto-field-group">
            <label class="presto-field-label">{$label}</label>
            <div class="presto-field-control">{$this->searchableSelect($name, $options, $value, $placeholder)}</div>
        </div>
HTML;
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
        return '';
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

        // Multi-level menu toggle
        document.querySelectorAll('[data-toggle="submenu"]').forEach(parent => {
            parent.addEventListener('click', () => {
                const wrapper = parent.closest('.presto-nav-group-wrapper');
                
                // Close others (Accordion style)
                document.querySelectorAll('.presto-nav-group-wrapper').forEach(other => {
                    if (other !== wrapper) other.classList.remove('is-open');
                });
                
                wrapper.classList.toggle('is-open');
            });
        });

        // Non-GET row actions
        JS;
    }
}
