<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Foundation\Application;
use Witals\Framework\Contracts\Auth\AuthContextInterface;
use Witals\Framework\Http\Request;
use Witals\Framework\Http\Response;

/**
 * Site Editor (Context Editor) — standalone page, không qua admin SPA.
 *
 * Render chrome của WordPress Site Editor (header, panel trái, canvas,
 * inspector phải) rồi mount editor JS build từ gutenberg-core/dist.
 *
 * Layout data load qua /api/admin/context/* (ContextBuilder).
 */
class SiteEditorController
{
    public function __construct(
        protected Application $app,
        protected ?AuthContextInterface $auth = null,
    ) {
    }

    public function show(Request $request, ?string $contextType = null): Response
    {
        // Safety net: middleware cũng đã chặn, nhưng không tin admin SPA.
        if ($this->auth?->getToken() === null) {
            $redirect = urlencode($request->uri());
            return Response::html('', 302, ['Location' => '/login?redirect=' . $redirect]);
        }

        $contextTypes = $this->resolveContextTypes();

        if ($contextType === null || !isset($contextTypes[$contextType])) {
            $contextType = array_key_first($contextTypes) ?: 'post';
        }

        $current = $contextTypes[$contextType] ?? ['name' => $contextType, 'label' => ucfirst($contextType)];

        $state = [
            'apiBase' => '/api/admin/context',
            'contextType' => $contextType,
            'contextTypes' => $contextTypes,
            'nonce' => $this->nonce(),
            'user' => $this->currentUser(),
            'previewUrl' => '/',
        ];

        return Response::html($this->renderPage($state, $current), 200, [
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
        ]);
    }

    /**
     * Serve editor JS/CSS build từ vendor/prestoworld/gutenberg-core/dist.
     */
    public function asset(string $path): Response
    {
        $safe = str_replace(['..', "\0"], '', $path);
        $file = $this->app->basePath('vendor/prestoworld/gutenberg-core/dist/' . ltrim($safe, '/'));

        if (is_file($file)) {
            return new Response(
                (string) file_get_contents($file),
                200,
                [
                    'Content-Type' => $this->mime($file),
                    'Cache-Control' => 'no-cache',
                ],
            );
        }

        return Response::html('Not found', 404);
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    protected function resolveContextTypes(): array
    {
        try {
            if (!$this->app->has(\PrestoWorld\Modules\ContextBuilder\ContextBuilder::class)) {
                return ['post' => ['name' => 'post', 'label' => 'Posts']];
            }

            $builder = $this->app->make(\PrestoWorld\Modules\ContextBuilder\ContextBuilder::class);
            $types = $builder->getContextTypes();

            // toArray trả về array of ContextType; chuẩn hoá về map name => {name,label,regions}
            $out = [];
            foreach ($types as $key => $type) {
                if (is_object($type)) {
                    $name = method_exists($type, 'getName') ? $type->getName() : (string) $key;
                    $label = method_exists($type, 'getLabel') ? $type->getLabel() : ucfirst($name);
                    $regions = method_exists($type, 'getRegions') ? $type->getRegions() : ['header', 'content', 'sidebar', 'footer'];
                    $out[$name] = ['name' => $name, 'label' => $label, 'regions' => $regions];
                } elseif (is_array($type)) {
                    $name = (string) ($type['name'] ?? $key);
                    $out[$name] = $type + ['name' => $name];
                }
            }

            return $out !== [] ? $out : ['post' => ['name' => 'post', 'label' => 'Posts']];
        } catch (\Throwable $e) {
            error_log('[SiteEditor] context types failed: ' . $e->getMessage());
            return ['post' => ['name' => 'post', 'label' => 'Posts']];
        }
    }

    protected function nonce(): string
    {
        return base64_encode(random_bytes(16));
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function currentUser(): ?array
    {
        try {
            $payload = $this->auth?->getToken()?->getPayload();
            if (!is_array($payload)) {
                return null;
            }

            return [
                'name' => $payload['name'] ?? ($payload['email'] ?? 'Admin'),
                'role' => $payload['role'] ?? 'administrator',
            ];
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @param array<string, mixed> $state
     * @param array<string, mixed> $current
     */
    protected function renderPage(array $state, array $current): string
    {
        $json = json_encode($state, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) ?: '{}';
        $title = htmlspecialchars((string) ($current['label'] ?? 'Site Editor'), ENT_QUOTES);
        $userName = htmlspecialchars((string) ($state['user']['name'] ?? 'Admin'), ENT_QUOTES);
        $csrf = htmlspecialchars((string) ($state['nonce'] ?? ''), ENT_QUOTES);

        $typeOptions = '';
        foreach ($state['contextTypes'] as $name => $type) {
            $label = htmlspecialchars((string) ($type['label'] ?? $name), ENT_QUOTES);
            $value = htmlspecialchars((string) $name, ENT_QUOTES);
            $sel = $name === $state['contextType'] ? ' selected' : '';
            $typeOptions .= "<option value=\"{$value}\"{$sel}>{$label}</option>";
        }

        return <<<HTML
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{$csrf}">
<title>Site Editor {$title} ‹ PrestoWorld</title>
<style>
:root{
  --se-bg:#f0f0f1;--se-surface:#fff;--se-border:#dcdcde;--se-text:#1e1e1e;
  --se-muted:#757575;--se-accent:#3858e9;--se-header-h:60px;--se-panel-w:280px;
  --se-toolbar-bg:#1e1e1e;--se-radius:2px;
}
*{box-sizing:border-box}
html,body{height:100%;margin:0}
body{
  font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Oxygen-Sans,Ubuntu,Cantarell,"Helvetica Neue",sans-serif;
  font-size:13px;color:var(--se-text);background:var(--se-bg);overflow:hidden;
}
button{font:inherit;cursor:pointer}

/* ===== Editor header ===== */
.se-header{
  position:fixed;top:0;left:0;right:0;height:var(--se-header-h);z-index:100;
  display:flex;align-items:center;gap:8px;padding:0 16px;
  background:var(--se-surface);border-bottom:1px solid var(--se-border);
}
.se-header-left{display:flex;align-items:center;gap:10px;flex:1;min-width:0}
.se-header-right{display:flex;align-items:center;gap:8px}
.se-wplogo{width:24px;height:24px;display:grid;place-items:center;color:#1e1e1e}
.se-back{width:36px;height:36px;border:none;background:transparent;border-radius:50%;
  display:grid;place-items:center;color:#1e1e1e}
.se-back:hover{background:#f0f0f1}
.se-title{display:flex;flex-direction:column;line-height:1.2}
.se-title b{font-size:15px;font-weight:600}
.se-title span{font-size:11px;color:var(--se-muted)}
.se-sep{width:1px;height:24px;background:var(--se-border);margin:0 4px}

.se-btn{height:36px;padding:0 14px;border-radius:var(--se-radius);border:1px solid var(--se-border);
  background:var(--se-surface);color:var(--se-text);font-weight:500}
.se-btn:hover{background:#f0f0f1}
.se-btn-primary{background:var(--se-accent);border-color:var(--se-accent);color:#fff}
.se-btn-primary:hover{background:#2f4bd8}
.se-btn-primary:disabled{opacity:.5;cursor:default}
.se-btn-ghost{border-color:transparent;background:transparent}
.se-btn-ghost:hover{background:#f0f0f1}
.se-save-state{font-size:11px;color:var(--se-muted);min-width:70px;text-align:right}

/* ===== Layout ===== */
.se-main{
  position:fixed;top:var(--se-header-h);left:0;right:0;bottom:0;display:flex;
}
.se-panel{
  width:var(--se-panel-w);background:var(--se-surface);display:flex;flex-direction:column;
  overflow:hidden;flex-shrink:0;
}
.se-panel-left{border-right:1px solid var(--se-border)}
.se-panel-right{border-left:1px solid var(--se-border)}
.se-panel-head{padding:14px 16px 10px;font-weight:600;font-size:13px;border-bottom:1px solid var(--se-border);
  display:flex;align-items:center;justify-content:space-between}
.se-panel-body{flex:1;overflow:auto;padding:8px}

/* ===== Canvas ===== */
.se-canvas-wrap{flex:1;min-width:0;display:flex;flex-direction:column;background:var(--se-bg)}
.se-canvas-bar{
  height:40px;display:flex;align-items:center;justify-content:center;gap:6px;
  background:var(--se-surface);border-bottom:1px solid var(--se-border);padding:0 12px;
}
.se-view{height:28px;padding:0 10px;border:1px solid var(--se-border);background:transparent;
  border-radius:14px;font-size:12px;color:var(--se-muted)}
.se-view.is-active{background:#1e1e1e;border-color:#1e1e1e;color:#fff}
.se-canvas{
  flex:1;overflow:auto;padding:32px;display:flex;justify-content:center;align-items:flex-start;
}
.se-frame{
  width:100%;max-width:1000px;background:#fff;border:1px solid var(--se-border);
  border-radius:4px;min-height:70vh;box-shadow:0 2px 8px rgba(0,0,0,.06);
  position:relative;
}

/* ===== Region + block (rendered by editor JS) ===== */
.se-region{position:relative;border:1px dashed transparent;transition:border-color .12s}
.se-region:hover{border-color:#c3c4c7}
.se-region.is-active{border-color:var(--se-accent)}
.se-region-label{
  position:absolute;top:-9px;left:8px;background:var(--se-accent);color:#fff;font-size:9px;
  padding:1px 6px;border-radius:2px;text-transform:uppercase;letter-spacing:.4px;display:none;
}
.se-region:hover .se-region-label,.se-region.is-active .se-region-label{display:block}
.se-block{position:relative;outline:1px dashed transparent;outline-offset:-1px;cursor:pointer;transition:outline-color .12s}
.se-block:hover{outline-color:#3858e9}
.se-block.is-selected{outline:2px solid var(--se-accent);outline-offset:-2px}
.se-block-empty{padding:14px;color:#999;font-style:italic;font-size:12px;text-align:center;
  border:1px dashed var(--se-border);border-radius:3px;margin:6px}

/* ===== Left panel: block library / types ===== */
.se-nav-item{display:flex;align-items:center;gap:9px;padding:9px 10px;border-radius:4px;
  cursor:pointer;color:var(--se-text);border:none;background:transparent;width:100%;text-align:left}
.se-nav-item:hover{background:#f0f0f1}
.se-nav-item.is-active{background:#f0f0f1;font-weight:600}
.se-nav-item .ico{width:16px;height:16px;flex-shrink:0;opacity:.7}
.se-search{width:100%;height:34px;padding:0 10px;border:1px solid var(--se-border);
  border-radius:4px;font:inherit;background:#fff}
.se-search:focus{outline:2px solid var(--se-accent);outline-offset:-1px;border-color:var(--se-accent)}
.se-block-grid{display:grid;grid-template-columns:1fr 1fr;gap:6px;margin-top:10px}
.se-block-card{border:1px solid var(--se-border);background:#fff;border-radius:4px;padding:12px 6px;
  display:flex;flex-direction:column;align-items:center;gap:6px;font-size:11px;color:var(--se-text);
  text-align:center}
.se-block-card:hover{border-color:var(--se-accent);box-shadow:0 1px 4px rgba(56,88,233,.15)}
.se-block-card .ico{width:22px;height:22px;opacity:.75}
.se-section-title{font-size:11px;text-transform:uppercase;letter-spacing:.5px;color:var(--se-muted);
  margin:14px 4px 6px;font-weight:600}
.se-type-switch{display:flex;flex-direction:column;gap:2px;margin-bottom:6px}

/* ===== Inspector ===== */
.se-insp-field{margin-bottom:12px}
.se-insp-label{display:block;font-size:11px;color:var(--se-muted);margin-bottom:5px;font-weight:500}
.se-insp-input,.se-insp-select,.se-insp-textarea{
  width:100%;padding:7px 9px;border:1px solid var(--se-border);border-radius:4px;font:inherit;background:#fff}
.se-insp-textarea{min-height:72px;resize:vertical;line-height:1.5}
.se-insp-input:focus,.se-insp-select:focus,.se-insp-textarea:focus{
  outline:2px solid var(--se-accent);outline-offset:-1px;border-color:var(--se-accent)}
.se-insp-row{display:flex;gap:8px}
.se-insp-row>*{flex:1}
.se-insp-empty{color:var(--se-muted);font-size:12px;padding:16px 4px;text-align:center;line-height:1.6}
.se-insp-actions{display:flex;gap:6px;margin-top:14px;padding-top:12px;border-top:1px solid var(--se-border)}
.se-insp-actions .se-btn{flex:1;padding:0 8px;font-size:12px}
.se-btn-danger{color:#d63638;border-color:#d63638}
.se-btn-danger:hover{background:#d63638;color:#fff}
.se-tag{display:inline-block;background:#f0f0f1;border:1px solid var(--se-border);border-radius:3px;
  padding:2px 7px;font-size:10px;font-family:ui-monospace,SFMono-Regular,Menlo,monospace;color:#444}
.se-count{font-size:11px;color:var(--se-muted)}

/* ===== Toast ===== */
.se-toast{position:fixed;bottom:24px;left:50%;transform:translateX(-50%) translateY(20px);
  background:#1e1e1e;color:#fff;padding:11px 18px;border-radius:4px;font-size:13px;opacity:0;
  pointer-events:none;transition:all .25s;z-index:9999}
.se-toast.is-show{opacity:1;transform:translateX(-50%) translateY(0)}
.se-toast.is-err{background:#d63638}

/* ===== Loading ===== */
.se-loading{position:absolute;inset:0;display:grid;place-items:center;color:var(--se-muted);font-size:13px}
@media (max-width:900px){
  .se-panel{position:fixed;top:var(--se-header-h);bottom:0;z-index:60;box-shadow:0 0 24px rgba(0,0,0,.2)}
  .se-panel-left{left:0}
  .se-panel-right{right:0}
}
</style>
</head>
<body>
  <!-- Editor header -->
  <header class="se-header">
    <div class="se-header-left">
      <a class="se-back" href="/dashboard" title="Về Dashboard" aria-label="Back to dashboard">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5"/><path d="m12 19-7-7 7-7"/></svg>
      </a>
      <span class="se-wplogo" aria-hidden="true">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2a10 10 0 1 0 10 10A10 10 0 0 0 12 2Zm-8.6 10a8.6 8.6 0 0 1 .7-3.4l4.7 12.8A8.61 8.61 0 0 1 3.4 12ZM12 20.6a8.6 8.6 0 0 1-2.6-.4l2.8-7.6 2.8 7.6a8.58 8.58 0 0 1-3 .4Zm1.2-12.6c.5 0 .9-.3.9-.7s-.4-.7-.9-.7-.9.3-.9.7.4.7.9.7Zm-3 4c-.4 0-.7.3-.7.6s.3.6.7.6h4.9l-2.5 6.8Zm9.7 3.4-2.8-7.6a3.5 3.5 0 0 0-.6-1.7l2.7 7.4a8.56 8.56 0 0 1 .7 1.9Z"/></svg>
      </span>
      <span class="se-sep"></span>
      <span class="se-title">
        <b>Site Editor</b>
        <span id="se-context-label">{$title}</span>
      </span>
    </div>
    <div class="se-header-right">
      <span class="se-save-state" id="se-save-state"></span>
      <button class="se-btn se-btn-ghost" id="se-preview" title="Xem trước">Xem trước</button>
      <button class="se-btn se-btn-primary" id="se-save" disabled>Lưu</button>
      <span class="se-sep"></span>
      <button class="se-btn se-btn-ghost" id="se-toggle-lib" title="Block library" aria-pressed="true">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
      </button>
      <button class="se-btn se-btn-ghost" id="se-toggle-insp" title="Inspector" aria-pressed="true">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 1 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 1 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 1 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 1 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1Z"/></svg>
      </button>
    </div>
  </header>

  <div class="se-main">
    <!-- Left panel: context switch + block library -->
    <aside class="se-panel se-panel-left" id="se-panel-left">
      <div class="se-panel-head">Chèn block</div>
      <div class="se-panel-body">
        <div class="se-section-title">Context</div>
        <select class="se-insp-select" id="se-context-switch" aria-label="Chọn context">{$typeOptions}</select>
        <div class="se-section-title">Thư viện</div>
        <input class="se-search" id="se-block-search" type="search" placeholder="Tìm block…" aria-label="Tìm block">
        <div class="se-block-grid" id="se-block-lib"></div>
      </div>
    </aside>

    <!-- Canvas -->
    <section class="se-canvas-wrap">
      <div class="se-canvas-bar">
        <button class="se-view is-active" data-view="desktop" title="Desktop">Desktop</button>
        <button class="se-view" data-view="tablet" title="Tablet">Tablet</button>
        <button class="se-view" data-view="mobile" title="Mobile">Mobile</button>
        <span class="se-count" id="se-region-count"></span>
      </div>
      <div class="se-canvas">
        <div class="se-frame" id="se-frame">
          <div class="se-loading" id="se-loading">Đang tải layout…</div>
        </div>
      </div>
    </section>

    <!-- Right: inspector -->
    <aside class="se-panel se-panel-right" id="se-panel-right">
      <div class="se-panel-head">
        <span id="se-insp-title">Cài đặt</span>
        <span class="se-tag" id="se-insp-tag"></span>
      </div>
      <div class="se-panel-body" id="se-inspector">
        <div class="se-insp-empty">Chọn một block trong canvas để chỉnh sửa cài đặt của nó.</div>
      </div>
    </aside>
  </div>

  <div class="se-toast" id="se-toast"></div>

  <script>window.__SITE_EDITOR_STATE__ = {$json};</script>
  <script type="module" src="/site-editor/assets/main.js"></script>
  <link rel="stylesheet" href="/site-editor/assets/main.css">
</body>
</html>
HTML;
    }

    protected function mime(string $path): string
    {
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        return match ($ext) {
            'js', 'mjs' => 'application/javascript; charset=utf-8',
            'css' => 'text/css; charset=utf-8',
            'html' => 'text/html; charset=utf-8',
            'json' => 'application/json',
            'svg' => 'image/svg+xml',
            'png' => 'image/png',
            'woff' => 'font/woff',
            'woff2' => 'font/woff2',
            default => function_exists('mime_content_type') && mime_content_type($path)
                ? (string) mime_content_type($path)
                : 'application/octet-stream',
        };
    }
}
