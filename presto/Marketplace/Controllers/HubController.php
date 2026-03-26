<?php

namespace PrestoWorld\Marketplace\Controllers;

use Prestoworld\MarketplaceSdk\Platform\HubEngine;
use PrestoWorld\Marketplace\WpOrg\WpOrgProvider;
use Witals\Framework\Http\Response;

/**
 * HubController
 * 
 * Implements the Hub API spec as defined in the guide.
 */
class HubController
{
    protected HubEngine $hub;

    public function __construct()
    {
        // For the main Hub, we combine WP Org and Local Providers
        // For this demo, we'll use WP Org as the primary data source
        $this->hub = new HubEngine(new WpOrgProvider());
    }

    /**
     * GET /api/plugins
     */
    public function plugins()
    {
        $params = $_GET;
        $params['type'] = 'plugin';
        return Response::json($this->hub->getCatalog($params));
    }

    /**
     * GET /api/themes
     */
    public function themes()
    {
        $params = $_GET;
        $params['type'] = 'theme';
        return Response::json($this->hub->getCatalog($params));
    }

    /**
     * GET /api/plugins/{slug}
     */
    public function info(string $slug)
    {
        $data = $this->hub->getInfo($slug, 'plugin');
        
        if (isset($data['error'])) {
            return Response::json($data, 404);
        }

        return Response::json($data);
    }

    /**
     * GET /api/plugins/{slug}/resolve
     * 
     * Resolves compatible version based on query params.
     */
    public function resolve(string $slug)
    {
        $pw_version = $_GET['prestoworld_version'] ?? '3.2.0';
        $php_version = $_GET['php_version'] ?? PHP_VERSION;
        $is_download = (isset($_GET['download']) && $_GET['download'] === '1');

        $info = $this->hub->getInfo($slug, 'plugin');
        
        if (isset($info['error'])) {
            return Response::json(['error' => 'No compatible version found'], 404);
        }

        // Logic check: version_compare($pw_version, $info['min_version'], '>=')
        // For this demo, we'll return the latest version.

        if ($is_download) {
            return redirect($info['download_url'] ?? '/');
        }

        return Response::json($info);
    }

    /**
     * POST /api/theme/ping
     */
    public function ping()
    {
        // Log telemetry data here
        return Response::json(['success' => true]);
    }
}
