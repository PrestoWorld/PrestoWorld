<?php

declare(strict_types=1);

namespace App\Foundation\Admin\Controllers;

use App\Foundation\Admin\AdminController;
use App\Foundation\Marketplace\Providers\WordPressOrgProvider;

/**
 * Class MarketplaceController
 * 
 * Renders the Plugin/Theme marketplace UI in the Admin Dashboard.
 */
class MarketplaceController extends AdminController
{
    protected WordPressOrgProvider $pluginProvider;
    protected WordPressOrgProvider $themeProvider;

    public function __construct(\Witals\Framework\Application $app)
    {
        parent::__construct($app);

        $this->pluginProvider = new WordPressOrgProvider('plugin');
        $this->themeProvider = new WordPressOrgProvider('theme');
    }

    /**
     * GET /dashboard/plugins
     * List all installed plugins/modules.
     */
    public function installedPlugins()
    {
        $moduleManager = app(\App\Foundation\Module\ModuleManager::class);
        $modules = $moduleManager->all();

        return $this->adminPage('Installed Plugins', view('admin/marketplace/installed-plugins', [
            'plugins' => $modules
        ]), [
            'new_url' => '/dashboard/plugins/install',
            'new_label' => 'Add New Plugin',
            'breadcrumbs' => [
                'Dashboard' => '/dashboard',
                'Plugins' => ''
            ]
        ]);
    }

    /**
     * GET /admin/plugins/install
     */
    public function plugins()
    {
        $tab = $_GET['tab'] ?? 'featured';
        $page = (int)($_GET['page'] ?? 1);
        $params = [
            'type' => 'plugin',
            'browse' => $tab,
            'page' => $page,
            'per_page' => 12,
            '_t' => time(),
        ];

        error_log('[Marketplace] Tab: ' . $tab);
        
        $result = $this->pluginProvider->fetchAll($params);
        $plugins = $result['items'] ?? [];
        $pagination = $result['pagination'] ?? ['page' => $page, 'per_page' => 12, 'total' => 0, 'total_pages' => 0];
        
        error_log('[Marketplace] Tab=' . $tab . ' | Count=' . count($plugins) . ' | Pages=' . $pagination['total_pages']);
        
        return $this->adminPage('Add Plugins', view('admin/marketplace/plugins', [
            'plugins' => $plugins,
            'pagination' => $pagination,
            'tab' => $tab
        ]));
    }

    /**
     * GET /admin/themes
     * List all installed themes.
     */
    public function themes()
    {
        $themeManager = app(\PrestoWorld\Theme\ThemeManager::class);
        $themes = $themeManager->all();

        return $this->adminPage('Themes Management', view('admin/theme/index', [
            'themes' => $themes
        ]));
    }

    /**
     * GET /admin/themes/install
     * List remote themes from the hub.
     */
    public function installThemes()
    {
        $tab = $_GET['tab'] ?? 'popular';
        $page = (int)($_GET['page'] ?? 1);
        $params = [
            'type' => 'theme',
            'browse' => $tab,
            'page' => $page,
            'per_page' => 12,
            '_t' => time(),
        ];

        error_log('[Marketplace] Tab: ' . $tab);
        
        $result = $this->themeProvider->fetchAll($params);
        $themes = $result['items'] ?? [];
        $pagination = $result['pagination'] ?? ['page' => $page, 'per_page' => 12, 'total' => 0, 'total_pages' => 0];
        
        error_log('[Marketplace] Tab=' . $tab . ' | Count=' . count($themes) . ' | Pages=' . $pagination['total_pages']);
        
        return $this->adminPage('Add Themes', view('admin/marketplace/themes', [
            'themes' => $themes,
            'pagination' => $pagination,
            'tab' => $tab
        ]));
    }
}
