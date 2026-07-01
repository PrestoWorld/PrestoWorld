<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use PrestoWorld\Contracts\Plugin\PluginStoreInterface;
use PrestoWorld\Plugin\PluginManager;
use Prestoworld\MarketplaceSdk\MarketplaceClient;
use Witals\Framework\Http\Response;

class PluginsController
{
    public function __construct(
        protected PluginStoreInterface $plugins,
        protected PluginManager $manager,
        protected MarketplaceClient $marketplace,
    ) {}

    public function plugins(): Response
    {
        $installed = $this->plugins->getInstalledPlugins();

        // Check for updates from all registered repositories
        $updates = $this->manager->checkUpdates();

        $plugins = array_values(array_map(fn(array $row) => [
            'id' => $row['name'],
            'name' => $row['name'],
            'desc' => $row['metadata']['desc'] ?? $row['metadata']['description'] ?? '',
            'version' => $row['version'],
            'author' => $row['metadata']['author'] ?? 'Unknown',
            'active' => $row['enabled'],
            'updateAvailable' => isset($updates[$row['name']]),
            'updateVersion' => $updates[$row['name']] ?? null,
            'category' => $row['metadata']['category'] ?? 'Uncategorized',
        ], $installed));

        return Response::json($plugins);
    }

    /**
     * Browse marketplace for available plugins/themes/extensions.
     *
     * GET /api/admin/plugins/browse?type=plugin&page=1&search=
     */
    public function browse(): Response
    {
        $type = $_GET['type'] ?? 'plugin';
        $page = (int)($_GET['page'] ?? 1);
        $search = $_GET['search'] ?? '';

        try {
            $result = $this->marketplace->browse([
                'type' => $type,
                'page' => max(1, $page),
                'per_page' => 30,
                'search' => $search,
            ]);

            return Response::json($result);
        } catch (\Throwable $e) {
            return Response::json([
                'data' => [],
                'pagination' => ['page' => 1, 'total' => 0, 'total_pages' => 0],
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Install a plugin from the marketplace.
     *
     * POST /api/admin/plugins/install
     * Body: { slug: string, version?: string, item_type?: string }
     */
    public function install(): Response
    {
        $body = json_decode(file_get_contents('php://input'), true);
        $slug = $body['slug'] ?? '';
        $version = $body['version'] ?? '*';

        if (empty($slug)) {
            return Response::json(['error' => 'Slug is required'], 400);
        }

        try {
            $downloadUrl = $this->marketplace->getDownloadUrl($slug, $version === '*' ? null : $version);
            if (!$downloadUrl) {
                return Response::json(['error' => "Plugin '{$slug}' not found in marketplace"], 404);
            }

            // In a full implementation, this would download and extract the ZIP
            // For now, return the download URL for the client to process
            return Response::json([
                'success' => true,
                'slug' => $slug,
                'download_url' => $downloadUrl,
                'message' => 'Plugin download URL resolved. Use the URL to download and install.',
            ]);
        } catch (\Throwable $e) {
            return Response::json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Toggle plugin activation status.
     *
     * POST /api/admin/plugins/toggle
     * Body: { plugin: string, active: bool }
     */
    public function toggle(): Response
    {
        $body = json_decode(file_get_contents('php://input'), true);
        $plugin = $body['plugin'] ?? '';
        $active = (bool)($body['active'] ?? false);

        if (empty($plugin)) {
            return Response::json(['error' => 'Plugin name is required'], 400);
        }

        if ($active) {
            $this->plugins->setEnabled($plugin, true);
        } else {
            $this->plugins->setEnabled($plugin, false);
        }

        return Response::json(['success' => true, 'plugin' => $plugin, 'active' => $active]);
    }

    /**
     * Update a plugin to its latest version.
     *
     * POST /api/admin/plugins/update
     * Body: { slug: string, current_version: string }
     */
    public function update(): Response
    {
        $body = json_decode(file_get_contents('php://input'), true);
        $slug = $body['slug'] ?? '';
        $currentVersion = $body['current_version'] ?? '';

        if (empty($slug) || empty($currentVersion)) {
            return Response::json(['error' => 'Slug and current_version are required'], 400);
        }

        try {
            $latestVersion = null;
            foreach ($this->manager->getRepositories() as $repo) {
                $result = $repo->hasUpdate($slug, $currentVersion);
                if ($result !== null) {
                    $latestVersion = $result;
                    break;
                }
            }

            if ($latestVersion === null) {
                return Response::json(['error' => 'No update available'], 404);
            }

            $downloadUrl = $this->marketplace->getDownloadUrl($slug, $latestVersion);

            return Response::json([
                'success' => true,
                'slug' => $slug,
                'latest_version' => $latestVersion,
                'download_url' => $downloadUrl,
            ]);
        } catch (\Throwable $e) {
            return Response::json(['error' => $e->getMessage()], 500);
        }
    }
}
