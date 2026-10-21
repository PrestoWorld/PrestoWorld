<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use Witals\Framework\Http\Request;
use Witals\Framework\Http\Response;
use Witals\Framework\Contracts\Auth\AuthContextInterface;
use PrestoWorld\Foundation\Admin\DashboardSubsystem;
use PrestoWorld\Admin\SkinManager;
use PrestoWorld\Contracts\Admin\Menu\MenuContextRepository;
use PrestoWorld\Contracts\Admin\Dashboard\DashboardScreenRegistryInterface;
use PrestoWorld\Contracts\Admin\Dashboard\DashboardWidgetRepository;
use PrestoWorld\Admin\ScreenOptions\ScreenOption;
use PrestoWorld\Admin\ScreenOptions\ScreenOptionsContext;
use PrestoWorld\Admin\AdminBar\AdminBarItem;
use PrestoWorld\Admin\AdminBar\AdminBarContext;

class SpaController
{
    protected bool $adminHooksDispatched = false;

    /** Widget-to-component mapping */
    protected const WIDGET_COMPONENTS = [
        'at-a-glance'  => 'StatCards',
        'quick-draft'  => 'QuickDraft',
        'activity'     => 'ActivityLog',
        'events-news'  => 'EventsNews',
    ];

    public function __construct(
        protected SkinManager $skins,
        protected MenuContextRepository $menu,
        protected DashboardWidgetRepository $widgets,
        protected DashboardScreenRegistryInterface $screens,
        protected ?AuthContextInterface $auth = null,
    ) {}

    protected const WP_SCREEN_MAP = [
        // Dashboard
        'index.php'           => 'dashboard',
        'admin.php'           => null, // determined by ?page= param

        // Posts
        'edit.php'            => 'posts',
        'post-new.php'        => 'post-new',
        'post.php'            => 'post',

        // Media
        'upload.php'          => 'upload',
        'media-new.php'       => 'media-new',

        // Pages
        'edit-pages.php'     => 'edit-pages',

        // Comments
        'edit-comments.php'  => 'edit-comments',

        // Appearance
        'themes.php'          => 'themes',
        'customize.php'       => 'customize',
        'widgets.php'         => 'widgets',
        'nav-menus.php'       => 'nav-menus',
        'theme-editor.php'    => 'theme-editor',

        // Plugins
        'plugins.php'         => 'plugins',
        'plugin-install.php'  => 'plugin-install',
        'plugin-editor.php'   => 'plugin-editor',

        // Users
        'users.php'           => 'users',
        'user-new.php'        => 'user-new',
        'user-edit.php'       => 'user-edit',
        'profile.php'         => 'profile',

        // Tools
        'tools.php'           => 'tools',
        'import.php'          => 'import',
        'export.php'          => 'export',
        'site-health.php'     => 'site-health',
        'site-health-info.php'=> 'site-health-info',

        // Settings
        'options-general.php' => 'settings',
        'options-writing.php' => 'options-writing',
        'options-reading.php' => 'options-reading',
        'options-discussion.php' => 'options-discussion',
        'options-media.php'   => 'options-media',
        'options-permalink.php' => 'options-privacy',
        'options-privacy.php' => 'options-privacy',

        // Updates
        'update-core.php'     => 'update-core',
    ];

    public function __invoke(Request $request): Response
    {
        $path = $request->path();

        // Serve new Dashboard module for /dashboard path
        if ($path === '/dashboard') {
            return $this->serveNewDashboard($request);
        }

        $skin = $this->resolveSkin($path, $request);
        $screenId = $this->resolveScreenId($request);

        $menuSections = $this->buildMenuSections();
        $screens = $this->buildScreens($menuSections);

        $screenTitle = 'Dashboard';
        foreach ($screens as $s) {
            if (($s['id'] ?? '') === $screenId) {
                $screenTitle = $s['title'] ?? 'Dashboard';
                break;
            }
        }

        $initialState = [
            'user' => $this->getUserState(),
            'screens' => $screens,
            'menuSections' => $menuSections,
            'subsystems' => $this->buildSubsystemNav(),
            'widgets' => $this->buildWidgets(),
            'screenOptions' => $this->buildScreenOptions(),
            'adminBar' => $this->buildAdminBar()->toArray(),
            'page' => [
                'path' => $request->path(),
                'title' => $screenTitle,
                'screenId' => $screenId,
            ],
        ];

        $html = $skin->renderLayout('', [
            'title' => $screenTitle,
            'initialState' => $initialState,
        ]);

        return Response::html($html);
    }

    /**
     * Serve the Seller Center SPA for /dashboard and its dynamic screen
     * routes (/dashboard/{screen}), e.g. /dashboard/orders.
     */
    public function dashboard(Request $request, ?string $screen = null): Response
    {
        // Gate the dashboard SPA shell behind a valid auth token.
        //
        // AdminAuthMiddleware now always enforces auth for /dashboard, but this
        // controller-level check stays as a safety net (same pattern as
        // SiteEditorController) so a valid token is still required to render
        // the SPA shell — a missing/expired token can never look "logged in".
        if ($this->auth?->getToken() === null) {
            $redirect = urlencode($request->uri());
            return Response::html('', 302, ['Location' => '/login?redirect=' . $redirect]);
        }

        return $this->serveNewDashboard($request, $screen);
    }

    protected function serveNewDashboard(Request $request, ?string $screen = null): Response
    {
        $dashboardDir = __DIR__ . '/../../../../public/assets/dashboard';

        if (is_dir($dashboardDir)) {
            [$cssFiles, $jsFiles] = $this->collectDashboardAssets($dashboardDir);

            // Build HTML with proper asset tags
            $html = '<!DOCTYPE html>' . "\n";
            $html .= '<html lang="en">' . "\n";
            $html .= '<head>' . "\n";
            $html .= '  <meta charset="UTF-8" />' . "\n";
            $html .= '  <meta name="viewport" content="width=device-width, initial-scale=1.0" />' . "\n";
            $html .= '  <title>PrestoWorld Dashboard</title>' . "\n";

            // Inject CSS
            foreach ($cssFiles as $cssFile) {
                $html .= '  <link rel="stylesheet" href="' . $cssFile . '" />' . "\n";
            }

            $html .= '</head>' . "\n";
            $html .= '<body>' . "\n";
            $html .= '  <div id="root"></div>' . "\n";

            // Inject initial state (screen resolved from the route)
            $initialState = $this->getNewDashboardInitialState($screen, $request->path());
            $html .= '  <script>window.__INITIAL_DASHBOARD_STATE__ = ' . json_encode($initialState, JSON_UNESCAPED_UNICODE) . ';</script>' . "\n";

            // Inject JS files (entry only — lazy chunks load on demand)
            foreach ($jsFiles as $jsFile) {
                $html .= '  <script src="' . $jsFile . '" type="module" defer></script>' . "\n";
            }

            $html .= '</body>' . "\n";
            $html .= '</html>';

            // Never cache the HTML so the latest bundle is always referenced
            return Response::html($html, 200, ['Cache-Control' => 'no-cache']);
        }

        // Fall back to old behavior
        return $this->__invoke($request);
    }

    /**
     * Resolve dashboard assets from the Vite manifest (entry + its CSS only)
     * so per-route lazy chunks are fetched on demand. Falls back to globbing
     * when the manifest is absent.
     *
     * @return array{0: list<string>, 1: list<string>}
     */
    protected function collectDashboardAssets(string $dashboardDir): array
    {
        $manifestPath = $dashboardDir . '/.vite/manifest.json';
        if (is_file($manifestPath)) {
            $manifest = json_decode((string) file_get_contents($manifestPath), true);

            if (is_array($manifest)) {
                foreach ($manifest as $node) {
                    if (is_array($node) && ($node['isEntry'] ?? false) === true) {
                        $cssFiles = [];
                        $jsFiles = [];

                        foreach ((array) ($node['css'] ?? []) as $css) {
                            $absolute = $dashboardDir . '/' . ltrim((string) $css, '/');
                            if (is_file($absolute)) {
                                $cssFiles[] = '/assets/dashboard/' . ltrim((string) $css, '/') . '?v=' . filemtime($absolute);
                            }
                        }

                        $entryAbsolute = $dashboardDir . '/' . ltrim((string) ($node['file'] ?? ''), '/');
                        if (($node['file'] ?? '') !== '' && is_file($entryAbsolute)) {
                            $jsFiles[] = '/assets/dashboard/' . ltrim((string) $node['file'], '/') . '?v=' . filemtime($entryAbsolute);
                        }

                        if ($jsFiles !== []) {
                            return [$cssFiles, $jsFiles];
                        }
                    }
                }
            }
        }

        // Legacy fallback: inject every asset under the output directory
        $cssFiles = [];
        $jsFiles = [];

        $cssDir = $dashboardDir . '/css';
        if (is_dir($cssDir)) {
            foreach (glob($cssDir . '/*.css') ?: [] as $cssFile) {
                $cssFiles[] = '/assets/dashboard/css/' . basename($cssFile) . '?v=' . filemtime($cssFile);
            }
        }

        $jsDir = $dashboardDir . '/js';
        if (is_dir($jsDir)) {
            $files = glob($jsDir . '/*.js') ?: [];
            // Sort to ensure vendor files come before main bundle
            usort($files, function ($a, $b) {
                $aMain = strpos(basename($a), 'dashboard.js') !== false;
                $bMain = strpos(basename($b), 'dashboard.js') !== false;
                if ($aMain && !$bMain) return 1;
                if (!$aMain && $bMain) return -1;
                return strcmp($a, $b);
            });
            foreach ($files as $jsFile) {
                $jsFiles[] = '/assets/dashboard/js/' . basename($jsFile) . '?v=' . filemtime($jsFile);
            }
        }

        return [$cssFiles, $jsFiles];
    }

    protected function getNewDashboardInitialState(?string $screenId = null, ?string $path = null): array
    {
        $menuSections = $this->buildMenuSections();
        $screens = $this->buildScreens($menuSections);

        // Update widget component mapping for new dashboard
        $newWidgetComponents = [
            'at-a-glance'  => 'StatCards',
            'quick-draft'  => 'QuickDraft',
            'activity'     => 'ActivityLog',
            'events-news'  => 'EventsNews',
        ];

        $widgets = [];
        foreach ($this->widgets->getWidgets() as $widget) {
            $id = $widget->getId();
            $component = $newWidgetComponents[$id] ?? '';

            $grid = match ($widget->getColumn()) {
                1 => 'half',
                2 => 'half',
                default => 'full',
            };

            $widgets[] = [
                'id' => $widget->getId(),
                'title' => $widget->getTitle(),
                'component' => $component,
                'grid' => $grid,
                'priority' => $widget->getPriority(),
                'visible' => $widget->isVisible(),
                'props' => [
                    'content' => $widget->getContent(),
                    'column' => $widget->getColumn(),
                ],
            ];
        }

        $subsystems = $this->buildSubsystemNav();

        $resolvedScreenId = ($screenId !== null && $screenId !== '') ? $screenId : 'overview';
        $title = 'Dashboard';
        foreach ($screens as $s) {
            if (($s['id'] ?? '') === $resolvedScreenId) {
                $title = $s['title'] ?? 'Dashboard';
                break;
            }
        }
        if ($title === 'Dashboard') {
            foreach ($subsystems as $sub) {
                if (($sub['screenId'] ?? $sub['id'] ?? '') === $resolvedScreenId) {
                    $title = $sub['label'] ?? 'Dashboard';
                    break;
                }
            }
        }

        return [
            'user' => [
                'name' => 'Administrator',
                'role' => 'admin',
                'avatar' => null,
            ],
            'screens' => $screens,
            'menuSections' => $menuSections,
            'subsystems' => $subsystems,
            'widgets' => $widgets,
            'page' => [
                'path' => $path ?? '/dashboard',
                'title' => $title,
                'screenId' => $resolvedScreenId,
            ],
        ];
    }

    protected function resolveSkin(string $path, Request $request): mixed
    {
        // /wp-admin/* routes always use the WordPress SSR skin
        if (str_starts_with($path, '/wp-admin')) {
            if ($this->skins->hasSkin('wordpress-classic')) {
                return $this->skins->getSkin('wordpress-classic');
            }
        }

        // ?skin= query param override
        $querySkin = $request->query('skin');
        if ($querySkin !== null && $this->skins->hasSkin($querySkin)) {
            return $this->skins->getSkin($querySkin);
        }

        return $this->skins->getActiveSkin();
    }

    protected function resolveScreenId(Request $request): string
    {
        $path = $request->path();
        $scriptName = basename($path);

        // Direct ?screen= param override
        $queryScreen = $request->query('screen');
        if ($queryScreen !== null) {
            return $queryScreen;
        }

        // Map WordPress script names to screen IDs
        if (isset(self::WP_SCREEN_MAP[$scriptName])) {
            $mapped = self::WP_SCREEN_MAP[$scriptName];
            if ($mapped !== null) {
                return $mapped;
            }
            // admin.php — get from ?page= param
            return $request->query('page', 'dashboard');
        }

        // /wp-admin/ root → dashboard
        if (in_array($path, ['/wp-admin', '/wp-admin/'], true)) {
            return 'dashboard';
        }

        return 'dashboard';
    }

    public function adminAjax(Request $request): Response
    {
        $action = $request->query('action', $request->post('action', ''));
        return Response::json([
            'success' => false,
            'data' => [],
            'error' => "WP Ajax action '{$action}' is not implemented in PrestoWorld.",
        ]);
    }

    public function initialState(Request $request): Response
    {
        return Response::json($this->getNewDashboardInitialState());
    }

    public function menu(Request $request): Response
    {
        return Response::json([
            'menuSections' => $this->buildMenuSections(),
            'subsystems' => $this->buildSubsystemNav(),
        ]);
    }

    public function dashboardWidgets(Request $request): Response
    {
        return Response::json($this->buildWidgets());
    }

    public function menuTree(): Response
    {
        return Response::json($this->menu->getTreeAsArray());
    }

    public function screenData(Request $request, string $screenId): Response
    {
        foreach ($this->buildScreens($this->buildMenuSections()) as $screen) {
            if (($screen['id'] ?? '') === $screenId) {
                return Response::json(['success' => true, 'data' => $screen]);
            }
        }

        return Response::json(['success' => false, 'error' => "Screen '{$screenId}' not found."], 404);
    }

    /**
     * Get all dashboard screens with component paths for dynamic loading.
     * Includes screens from all registered providers (modules, plugins, themes).
     */
    public function dashboardScreens(Request $request): Response
    {
        $this->dispatchAdminHooks();

        $screens = [];
        foreach ($this->screens->getScreens() as $screen) {
            $screens[] = $screen->toArray();
        }

        // Also include fallback screens from menu items
        $menuSections = $this->buildMenuSections();
        $seen = [];
        foreach ($menuSections as $section) {
            foreach ($section['items'] ?? [] as $item) {
                $screenId = $item['screenId'] ?? null;
                if ($screenId === null || $screenId === '' || isset($seen[$screenId])) {
                    continue;
                }
                $seen[$screenId] = true;
                $screens[] = [
                    'id' => $screenId,
                    'title' => $item['label'] ?? $screenId,
                    'icon' => $item['icon'] ?? 'Circle',
                    'position' => $item['priority'] ?? count($screens) * 10,
                    'component' => null,
                    'source' => 'core',
                    'settings' => [],
                ];
            }
        }

        return Response::json([
            'success' => true,
            'data' => $screens,
        ]);
    }

    /**
     * Fire WordPress-compatible admin registration actions so plugins, modules
     * and themes can add nav items, screens and dashboard widgets through
     * add_action() before the SPA state is serialized.
     */
    protected function dispatchAdminHooks(): void
    {
        if ($this->adminHooksDispatched) {
            return;
        }

        $this->adminHooksDispatched = true;

        do_action('admin.dashboard.nav', $this->menu);
        do_action('admin.screen.register', $this->screens);
        do_action('admin.dashboard.widgets', $this->widgets);
    }

    /** Build screens list from the screen registry plus registered menu items */
    protected function buildScreens(array $menuSections): array
    {
        $this->dispatchAdminHooks();

        $screens = [];
        foreach ($this->screens->getScreens() as $screen) {
            $screenData = $screen->toArray();
            
            // Add module asset URL for module-owned screens
            $screenData['componentUrl'] = $this->getModuleComponentUrl($screen->getId());
            
            $screens[$screen->getId()] = $screenData;
        }

        // Fall back to menu items for screens not present in the registry
        $seen = [];
        foreach ($menuSections as $section) {
            foreach ($section['items'] ?? [] as $item) {
                $screenId = $item['screenId'] ?? null;
                if ($screenId === null || $screenId === '' || isset($screens[$screenId]) || isset($seen[$screenId])) {
                    continue;
                }
                $seen[$screenId] = true;
                $screens[$screenId] = [
                    'id' => $screenId,
                    'title' => $item['label'] ?? $screenId,
                    'icon' => $item['icon'] ?? 'Circle',
                    'position' => $item['priority'] ?? count($screens) * 10,
                    'component' => null,
                    'componentUrl' => null,
                    'source' => 'core',
                    'settings' => [],
                ];
            }
        }

        return array_values($screens);
    }

    /**
     * Get the module asset URL for a screen component.
     * Maps screen IDs to their module's asset base URL + entry file.
     */
    protected function getModuleComponentUrl(string $screenId): ?string
    {
        $moduleMap = [
            // Suppliers module
            'suppliers' => '/assets/modules/suppliers/suppliers-screens.',
            'depots' => '/assets/modules/suppliers/suppliers-screens.',
            'departments' => '/assets/modules/suppliers/suppliers-screens.',
            'users' => '/assets/modules/suppliers/suppliers-screens.',
            
            // Ecommerce module
            'products' => '/assets/modules/ecommerce/ecommerce-screens.',
            'customers' => '/assets/modules/ecommerce/ecommerce-screens.',
            'orders' => '/assets/modules/ecommerce/ecommerce-screens.',
            'bills' => '/assets/modules/ecommerce/ecommerce-screens.',
            'ecom' => '/assets/modules/ecommerce/ecommerce-screens.',
            'invoices' => '/assets/modules/ecommerce/ecommerce-screens.',
            
            // Inventory module
            'inventory' => '/assets/modules/inventory/inventory-screens.',
            'inventory-movements' => '/assets/modules/inventory/inventory-screens.',
            
            // Accounting module
            'accounting' => '/assets/modules/accounting/accounting-screens.',
            
            // MarketingCampaign module
            'promotions' => '/assets/modules/marketingcampaign/marketingcampaign-screens.',
            
            // ZaloBusiness module
            'zalo' => '/assets/modules/zalobusiness/zalobusiness-screens.',
            
            // BookCatalog module
            'books' => '/assets/modules/bookcatalog/bookcatalog-screens.',
        ];

        $baseUrl = $moduleMap[$screenId] ?? null;
        
        if ($baseUrl === null) {
            return null;
        }

        // Get the manifest to find the actual hashed filename
        $manifestPath = $this->getManifestPathForScreen($screenId);
        if ($manifestPath && is_file($manifestPath)) {
            $manifest = json_decode(file_get_contents($manifestPath), true);
            if (is_array($manifest)) {
                foreach ($manifest as $node) {
                    if (is_array($node) && ($node['isEntry'] ?? false) === true && ($node['file'] ?? '')) {
                        return $baseUrl . $node['file'];
                    }
                }
            }
        }

        return $baseUrl . 'index.js';
    }

    protected function getManifestPathForScreen(string $screenId): ?string
    {
        $moduleMap = [
            'suppliers' => 'suppliers',
            'depots' => 'suppliers',
            'departments' => 'suppliers',
            'users' => 'suppliers',
            'products' => 'ecommerce',
            'customers' => 'ecommerce',
            'orders' => 'ecommerce',
            'bills' => 'ecommerce',
            'ecom' => 'ecommerce',
            'invoices' => 'ecommerce',
            'inventory' => 'inventory',
            'inventory-movements' => 'inventory',
            'accounting' => 'accounting',
            'promotions' => 'marketingcampaign',
            'zalo' => 'zalobusiness',
            'books' => 'bookcatalog',
        ];

        $module = $moduleMap[$screenId] ?? null;
        
        if ($module === null) {
            return null;
        }

        return base_path("public/assets/modules/{$module}/.vite/manifest.json");
    }

    /** Build sidebar sections from the registered menu tree */
    protected function buildMenuSections(): array
    {
        $this->dispatchAdminHooks();

        $sections = [];
        
        // Get sections from the new menu structure (sections with groups)
        $menuSections = $this->menu->getSections();
        
        foreach ($menuSections as $section) {
            // Skip the subsystem section - it's serialized separately via buildSubsystemNav()
            if (($section['id'] ?? '') === 'modules') {
                // Include modules section but filter out subsystem items
                $filteredGroups = array_filter($section['groups'] ?? [], function($group) {
                    return !isset($group['attributes']['subLabel']);
                });
                if (empty($filteredGroups)) {
                    continue;
                }
                $section['groups'] = array_values($filteredGroups);
            }
            
            if (empty($section['groups'] ?? [])) {
                continue;
            }
            
            $groups = array_values($section['groups']);
            // Ensure overview section only contains dashboard group
            if ((($section['id'] ?? '') === 'overview')) {
                $groups = array_filter($groups, function($group) {
                    return ((($group['id'] ?? '') === 'dashboard-group')) || ((($group['screenId'] ?? '') === 'dashboard'));
                });
                $groups = array_values($groups);
            }
            
            // Use the first group as the primary screenId/icon for the section
            $firstGroup = $groups[0] ?? [];
            $screenId = $firstGroup['screenId'] ?? $firstGroup['id'] ?? '';
            $icon = $firstGroup['icon'] ?? $section['icon'] ?? 'Circle';
            
            // Build items from groups — children of a group (registered via
            // registerItem) are serialized as `items` for the nested flyout.
            $items = array_map(function(array $group) use ($icon) {
                $groupScreenId = $group['screenId'] ?? $group['id'] ?? '';
                $item = [
                    'id' => $group['id'] ?? $group['screenId'] ?? '',
                    'screenId' => $groupScreenId,
                    'label' => $group['label'] ?? '',
                    'icon' => $group['icon'] ?? $icon,
                    'url' => $group['url'] ?? '',
                    'priority' => $group['priority'] ?? 10,
                ];

                $children = array_values($group['items'] ?? []);
                if ($children !== []) {
                    usort($children, fn(array $a, array $b) => ($a['priority'] ?? 10) <=> ($b['priority'] ?? 10));
                    $item['items'] = $children;
                }

                return $item;
            }, $groups);
            
            $sections[] = [
                'id' => $section['id'] ?? '',
                'title' => $section['label'] ?? '',
                'priority' => $section['priority'] ?? 10,
                'icon' => $section['icon'] ?? 'Circle',
                'screenId' => $screenId,
                'items' => $items,
            ];
        }

        // Sort sections by priority
        usort($sections, fn($a, $b) => ($a['priority'] ?? 10) <=> ($b['priority'] ?? 10));

        return $sections;
    }

    /**
     * Serialize the Seller Center subsystem catalog ("Danh Mục Phân Hệ")
     * registered by modules through the dashboard menu context.
     * Subsystems are now distributed across sections, identified by having a 'subLabel' attribute.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function buildSubsystemNav(): array
    {
        $this->dispatchAdminHooks();

        $subsystems = [];
        
        foreach ($this->menu->getSections() as $section) {
            // Skip modules section - it's handled separately in sidebar
            if (($section['id'] ?? '') === 'modules') {
                continue;
            }
            
            foreach ($section['groups'] ?? [] as $group) {
                $attributes = $group['attributes'] ?? [];
                
                // Only include groups that have a subLabel (marks them as subsystem items)
                if (!isset($attributes['subLabel']) || $attributes['subLabel'] === '') {
                    continue;
                }

                $screenId = $group['screenId'] ?? $group['id'] ?? '';
                $subsystems[] = [
                    'id' => $screenId,
                    'label' => $group['label'] ?? '',
                    'subLabel' => $attributes['subLabel'] ?? '',
                    'icon' => $group['icon'] ?? 'Circle',
                    'screenId' => $screenId,
                    'priority' => $group['priority'] ?? 10,
                    'section' => $section['id'] ?? '',
                ];
            }
        }

        // Sort by priority
        usort($subsystems, fn($a, $b) => ($a['priority'] ?? 10) <=> ($b['priority'] ?? 10));

        return $subsystems;
    }

    /** Build widget definitions with SPA component mapping */
    protected function buildWidgets(): array
    {
        $definitions = [];

        foreach ($this->widgets->getWidgets() as $widget) {
            $id = $widget->getId();
            $component = self::WIDGET_COMPONENTS[$id] ?? '';

            $grid = match ($widget->getColumn()) {
                1 => 'half',
                2 => 'half',
                default => 'full',
            };

            $definitions[] = [
                'id' => $widget->getId(),
                'title' => $widget->getTitle(),
                'component' => $component,
                'grid' => $grid,
                'priority' => $widget->getPriority(),
                'visible' => $widget->isVisible(),
                'props' => [
                    'content' => $widget->getContent(),
                    'column' => $widget->getColumn(),
                ],
            ];
        }

        return $definitions;
    }

    protected function buildScreenOptions(): array
    {
        $postsOptions = new ScreenOptionsContext('posts', 'Posts Settings');
        $postsOptions->addOption(new ScreenOption(
            id: 'posts_per_page',
            label: 'Posts per page',
            type: 'number',
            default: 20,
        ));
        $postsOptions->addOption(new ScreenOption(
            id: 'show_comments',
            label: 'Show comment count',
            type: 'checkbox',
            default: true,
        ));

        $pluginsOptions = new ScreenOptionsContext('plugins', 'Plugins Settings');
        $pluginsOptions->addOption(new ScreenOption(
            id: 'show_deprecated',
            label: 'Show deprecated plugins',
            type: 'checkbox',
            default: false,
        ));

        return [
            $postsOptions->toArray(),
            $pluginsOptions->toArray(),
        ];
    }

    protected function buildAdminBar(): AdminBarContext
    {
        $bar = new AdminBarContext();

        $bar->addItem(new AdminBarItem(
            id: 'visit-site',
            label: 'Visit Site',
            icon: 'Globe',
            href: '/',
            type: 'link',
        ));

        $notification = new AdminBarItem(
            id: 'notifications',
            label: 'Notifications',
            icon: 'Bell',
            type: 'notification',
            badge: 3,
        );

        $bar->addItem(new AdminBarItem(
            id: 'new-post',
            label: 'New Post',
            icon: 'Plus',
            type: 'button',
        ));

        $bar->addItem($notification);

        return $bar;
    }

    protected function getUserState(): array
    {
        return [
            'name' => 'Administrator',
            'role' => 'admin',
            'avatar' => null,
        ];
    }
}