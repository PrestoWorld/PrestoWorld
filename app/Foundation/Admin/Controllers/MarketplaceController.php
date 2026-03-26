<?php

namespace App\Foundation\Admin\Controllers;

use App\Foundation\Admin\AdminController;
use PrestoWorld\Marketplace\WpOrg\WpOrgProvider;
use Prestoworld\MarketplaceSdk\Platform\HubEngine;

/**
 * Class MarketplaceController
 * 
 * Renders the Plugin/Theme marketplace UI in the Admin Dashboard.
 */
class MarketplaceController extends AdminController
{
    protected HubEngine $hub;

    public function __construct(\Witals\Framework\Application $app)
    {
        parent::__construct($app);

        // Use WpOrg as the source for the marketplace UI
        $this->hub = new HubEngine(new WpOrgProvider());
    }

    /**
     * GET /admin/plugins/install
     */
    public function plugins()
    {
        $tab = $_GET['tab'] ?? 'featured';
        $params = [
            'type' => 'plugin',
            'page' => $_GET['page'] ?? 1,
            'per_page' => 12
        ];

        $catalog = $this->hub->getCatalog($params);
        
        return $this->adminPage('Add Plugins', view('admin/marketplace/plugins', [
            'plugins' => $catalog['data'],
            'pagination' => $catalog['pagination'],
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
        $params = [
            'type' => 'theme',
            'page' => $_GET['page'] ?? 1,
            'per_page' => 12
        ];

        $catalog = $this->hub->getCatalog($params);
        
        return $this->adminPage('Add Themes', view('admin/marketplace/themes', [
            'themes' => $catalog['data'],
            'pagination' => $catalog['pagination'],
            'tab' => $tab
        ]));
    }
}
