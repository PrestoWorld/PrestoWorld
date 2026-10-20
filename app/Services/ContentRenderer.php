<?php

declare(strict_types=1);

namespace App\Services;

use PrestoWorld\Modules\ClassicTheme\ClassicThemeEngine;
use PrestoWorld\Modules\ClassicTheme\ThemeDetector;
use Presto\Context\Loader as ContextLoader;
use App\Contracts\Services\ContentRenderer as ContentRendererContract;
use App\Contracts\Services\RenderedContent;
use PrestoWorld\Core\AssetManager;
use PrestoWorld\Gutenberg\Gutenberg;
use Witals\Framework\Contracts\Container;

class ContentRenderer implements ContentRendererContract
{
    /** Minimal styling so the frontend admin bar is visible as a fixed top bar. */
    private const ADMIN_BAR_CSS = <<<'CSS'
<style id="presto-admin-bar-css">
#presto-adminbar{position:fixed;top:0;left:0;right:0;height:32px;z-index:99999;background:#1d2327;color:#f0f0f1;font:13px/32px -apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif;box-shadow:0 1px 2px rgba(0,0,0,.2)}
#presto-adminbar .presto-admin-bar-inner{display:flex;justify-content:space-between;align-items:center;height:32px;padding:0 12px}
#presto-adminbar ul{display:flex;align-items:center;gap:2px;margin:0;padding:0;list-style:none}
#presto-adminbar .presto-admin-bar-item{display:flex}
#presto-adminbar .presto-admin-bar-link{display:flex;align-items:center;gap:6px;color:#f0f0f1;text-decoration:none;padding:0 10px;height:32px;border-radius:0;white-space:nowrap}
#presto-adminbar .presto-admin-bar-link:hover{background:#2c3338;color:#72aee6}
#presto-adminbar .presto-admin-bar-username{opacity:.9}
#presto-adminbar .presto-admin-bar-avatar{width:20px;height:20px;border-radius:50%}
#presto-adminbar .presto-admin-bar-icon{display:inline-flex;width:16px;height:16px;opacity:.85}
</style>
CSS;

    private ?ClassicThemeEngine $classicEngine = null;

    private bool $resolved = false;

    private bool $enqueueScriptsDone = false;

    public function __construct(
        private ContextLoader $contextLoader,
        private Container $container,
        private string $themePath = '',
    ) {}

    public function render(string $template, array $post = []): RenderedContent
    {
        $classic = $this->classicEngine();
        if ($classic !== null) {
            return $classic->render($template, $post);
        }

        // Block theme: let the theme enqueue its assets (style.css, ...).
        $this->dispatchEnqueueScripts();

        $body = $this->contextLoader->renderTemplate($template, $post);
        $styles = $this->collectStyles();

        // Frontend admin bar (logged-in users) — prepend to the body.
        $body = $this->injectAdminBar($body, $post);

        // Partial body + styles — HtmlComposer wraps the full document.
        return new RenderedContent($body, $styles, complete: false);
    }

    public function supports(string $template): bool
    {
        $classic = $this->classicEngine();
        if ($classic !== null) {
            return $classic->supports($template);
        }

        return $this->contextLoader->supports($template);
    }

    private function dispatchEnqueueScripts(): void
    {
        if ($this->enqueueScriptsDone) {
            return;
        }

        $this->enqueueScriptsDone = true;

        if (function_exists('do_action')) {
            do_action('wp_enqueue_scripts');
        }
    }

    /**
     * theme.json/base CSS from the Gutenberg kernel + theme-queued styles
     * (wp_enqueue_style → AssetManager) inlined into <style> blocks.
     */
    private function collectStyles(): string
    {
        $css = '';

        $kernel = Gutenberg::kernel();

        if ($kernel !== null) {
            $css .= '<style id="gutenberg-theme-css">' . "\n"
                . $kernel->themeCss() . "\n</style>\n";
        }

        foreach (AssetManager::queuedStyles() as $handle => $asset) {
            $inline = $this->inlineStyleContent((string) ($asset['src'] ?? ''));

            if ($inline !== null) {
                $css .= '<style id="' . htmlspecialchars($handle, ENT_QUOTES) . '-inline-css">' . "\n"
                    . $inline . "\n</style>\n";
                continue;
            }

            $src = (string) ($asset['src'] ?? '');

            if ($src !== '') {
                $css .= '<link rel="stylesheet" id="'
                    . htmlspecialchars($handle, ENT_QUOTES)
                    . '-css" href="' . htmlspecialchars($src, ENT_QUOTES) . "\" />\n";
            }
        }

        return $css;
    }

    /**
     * Resolve a queued stylesheet URL to a local file and return its contents.
     */
    private function inlineStyleContent(string $src): ?string
    {
        if ($src === '') {
            return null;
        }

        $themeDir = $this->themePath !== '' ? rtrim($this->themePath, '/') : null;

        // Strip origin + query to map a URL back onto the local path.
        $path = (string) preg_replace('#^[a-z]+://[^/]+#i', '', $src);
        $path = (string) preg_replace('/\?.*$/', '', $path);

        if ($themeDir !== null && $path !== '') {
            $candidate = $themeDir . $path;

            if (str_starts_with($path, '/') && is_file($candidate)) {
                $contents = file_get_contents($candidate);

                return $contents === false ? null : $contents;
            }
        }

        if (is_file($src)) {
            $contents = file_get_contents($src);

            return $contents === false ? null : $contents;
        }

        return null;
    }

    /**
     * Prepend the frontend admin bar (PrestoWorld\Admin\AdminBar) to the body
     * for logged-in users. Uses the Witals AuthContext (real session token set
     * on login), not the legacy pw_remember/UserRepository path. Renders nothing
     * when the admin module is absent or the visitor is a guest.
     */
    private function injectAdminBar(string $body, array $post = []): string
    {
        if (!class_exists(\PrestoWorld\Admin\AdminBar\AdminBarRenderer::class)) {
            return $body;
        }

        $user = $this->currentAdminBarUser();
        if ($user === null) {
            return $body;
        }

        $contextClass = \PrestoWorld\Contracts\Admin\AdminBar\AdminBarContext::class;
        if (!$this->container->has($contextClass)) {
            return $body;
        }

        $renderer = new \PrestoWorld\Admin\AdminBar\AdminBarRenderer(
            $this->container->make($contextClass),
        );

        $html = self::ADMIN_BAR_CSS . $renderer->render($user);

        // Push the page content below the fixed bar.
        return '<style>body{padding-top:32px}</style>' . $html . $body;
    }

    /**
     * Current admin-bar user from the auth token cookie ('token') + TokenStorage.
     *
     * Reads the token cookie directly and loads it from storage (global
     * singleton) rather than relying on the request-scoped AuthContext — the
     * ContentRenderer is a long-lived singleton that may resolve outside the
     * AuthMiddleware runScope.
     *
     * @return array<string, mixed>|null
     */
    private function currentAdminBarUser(): ?array
    {
        $tokenId = $_COOKIE['token'] ?? null;
        if (!is_string($tokenId) || $tokenId === '') {
            return null;
        }

        $storageClass = \Witals\Framework\Contracts\Auth\TokenStorageInterface::class;
        if (!$this->container->has($storageClass)) {
            return null;
        }

        $token = $this->container->make($storageClass)->load($tokenId);
        if ($token === null) {
            return null;
        }

        $payload = $token->getPayload();

        return [
            'name' => $payload['name'] ?? ($payload['email'] ?? 'Admin'),
        ];
    }

    /**
     * Resolve the classic engine only when the active theme is a legacy
     * (pre-Gutenberg, PHP-template) WordPress theme. New/block themes are
     * rendered by the ContextBuilder ContextLoader (the primary template engine).
     */
    private function classicEngine(): ?ClassicThemeEngine
    {
        if ($this->resolved) {
            return $this->classicEngine;
        }

        $this->resolved = true;

        if ($this->themePath === '' || !ThemeDetector::isClassic($this->themePath)) {
            return null;
        }

        if ($this->container->has(ClassicThemeEngine::class)) {
            $engine = $this->container->make(ClassicThemeEngine::class);
            if ($engine instanceof ClassicThemeEngine) {
                $this->classicEngine = $engine;
            }
        }

        return $this->classicEngine;
    }
}
