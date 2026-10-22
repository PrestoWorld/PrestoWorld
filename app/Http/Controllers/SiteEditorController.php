<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Foundation\Application;
use App\Http\Rest\WpNonce;
use Witals\Framework\Contracts\Auth\AuthContextInterface;
use Witals\Framework\Http\Request;
use Witals\Framework\Http\Response;

/**
 * Site Editor — mount WordPress Site Editor thật (`wp.presto.mountSiteEditor`)
 * từ bundle webpack (public/assets/gutenberg), gọi initializeEditor(id, settings).
 *
 * REST đi qua /wp-json/ + X-WP-Nonce (WpNonce). Layout data của Context Builder
 * vẫn load qua /api/admin/context/* và được inject qua settings.presto.
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

        $state = [
            'restRoot' => '/wp-json/',
            'restNonce' => WpNonce::create($this->app),
            'siteUrl' => $this->siteUrl(),
            'dashboardUrl' => '/dashboard',
            'isBlockTheme' => true,
            'user' => $this->currentUser(),
            'contextType' => $contextType,
            'contextTypes' => $contextTypes,
            'previewUrl' => '/',
        ];

        return Response::html($this->renderPage($state), 200, [
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

    protected function siteUrl(): string
    {
        $configured = (string) (getenv('APP_URL') ?: '');
        if ($configured !== '') {
            return rtrim($configured, '/');
        }

        $https = strtolower((string) ($_SERVER['HTTPS'] ?? '')) !== ''
            && strtolower((string) $_SERVER['HTTPS']) !== 'off';
        $host = (string) ($_SERVER['HTTP_HOST'] ?? 'prestoworld.org');

        return ($https ? 'https' : 'http') . '://' . $host;
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
     */
    protected function renderPage(array $state): string
    {
        $title = 'Site Editor';
        $rootUrl = htmlspecialchars((string) ($state['restRoot'] ?? '/wp-json/'), ENT_QUOTES);
        $nonce = htmlspecialchars((string) ($state['restNonce'] ?? ''), ENT_QUOTES);

        $editorSettings = [
            'siteUrl' => (string) ($state['siteUrl'] ?? '/'),
            'homeUrl' => (string) ($state['previewUrl'] ?? '/'),
            'adminUrl' => (string) ($state['dashboardUrl'] ?? '/dashboard'),
            '__experimentalDashboardLink' => (string) ($state['dashboardUrl'] ?? '/dashboard'),
            '__experimentalDashboardLinkText' => 'Dashboard',
            'isBlockTheme' => (bool) ($state['isBlockTheme'] ?? true),
            'presto' => [
                'apiBase' => '/api/admin/context',
                'contextType' => (string) ($state['contextType'] ?? 'post'),
                'contextTypes' => $state['contextTypes'] ?? [],
                'user' => $state['user'] ?? null,
            ],
        ];

        $settingsJson = json_encode($editorSettings, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_UNESCAPED_UNICODE) ?: '{}';

        return <<<HTML
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>{$title} ‹ PrestoWorld</title>
<link rel="stylesheet" href="/assets/gutenberg/common.css">
<link rel="stylesheet" href="/assets/gutenberg/components.css">
<link rel="stylesheet" href="/assets/gutenberg/presto-styles.css">
<style>
html,body{height:100%;margin:0;padding:0}
body{overflow:hidden;background:#f0f0f1;font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Helvetica,Arial,sans-serif}
#site-editor-root{height:100%;width:100%}
#site-editor-root:empty::after{
  content:"Đang tải Site Editor…";display:grid;place-items:center;height:100%;
  color:#757575;font-size:13px;
}
</style>
</head>
<body class="block-editor-page">
<div id="site-editor-root"></div>
<script>
window.IS_GUTENBERG_PLUGIN = true;
window.wpApiSettings = { root: "{$rootUrl}", nonce: "{$nonce}", version: "6.7" };
window.__SITE_EDITOR_SETTINGS__ = {$settingsJson};
</script>
<script src="/assets/gutenberg/runtime.min.js"></script>
<script src="/assets/gutenberg/vendor.min.js"></script>
<script src="/assets/gutenberg/common.min.js"></script>
<script src="/assets/gutenberg/data.min.js"></script>
<script src="/assets/gutenberg/core-data.min.js"></script>
<script src="/assets/gutenberg/presto.min.js"></script>
<script>
(function () {
  try {
    window.wp.presto.mountSiteEditor('site-editor-root', window.__SITE_EDITOR_SETTINGS__, {
      rootUrl: window.wpApiSettings.root,
      nonce: window.wpApiSettings.nonce
    });
  } catch (err) {
    console.error('[SiteEditor] mount failed', err);
    var mount = document.getElementById('site-editor-root');
    if (mount) { mount.textContent = 'Không khởi tạo được Site Editor — xem console.'; }
  }
})();
</script>
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
