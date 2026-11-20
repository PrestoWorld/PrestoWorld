<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use PrestoWorld\Contracts\Plugin\PluginStoreInterface;
use PrestoWorld\Core\Plugin\PluginBlueprint;
use PrestoWorld\Core\Plugin\PluginDiscoveryService;
use PrestoWorld\Core\Plugin\SecurityScanner;
use PrestoWorld\Plugin\PluginManager;
use Prestoworld\MarketplaceSdk\MarketplaceClient;
use Witals\Framework\Http\Response;

class PluginsController
{
    public function __construct(
        protected PluginStoreInterface $plugins,
        protected PluginManager $manager,
        protected MarketplaceClient $marketplace,
        protected PluginDiscoveryService $discovery,
    ) {}

    /**
     * List all plugins — both installed and discovered on disk.
     *
     * GET /api/admin/plugins
     *
     * Response shape is a flat list where each entry has:
     *   - id:           slug
     *   - name:         display name (from header/manifest)
     *   - slug:         directory name
     *   - desc:         description
     *   - version:      installed version or discovered version
     *   - author:       author
     *   - active:       bool — enabled in plugin_registry
     *   - installed:    bool — present in plugin_registry
     *   - source:       manifest | header | heuristic
     *   - updateAvailable: bool
     *   - updateVersion:   string|null
     *   - category:     first tag or "Uncategorized"
     */
    public function plugins(): Response
    {
        $installedRows = $this->plugins->getInstalledPlugins();
        $updates = $this->manager->checkUpdates();
        $discovered = $this->discovery->all();

        $plugins = [];

        // 1) Discovered plugins first (this is what fills the list).
        foreach ($discovered as $slug => $bp) {
            if (!$bp->isValid) {
                continue;
            }

            $installed = isset($installedRows[$slug]);
            $row = $installedRows[$slug] ?? [];

            $plugins[] = [
                'id' => $slug,
                'name' => $bp->name,
                'slug' => $slug,
                'desc' => $bp->description,
                'version' => $installed ? (string) ($row['version'] ?? $bp->version) : $bp->version,
                'author' => $bp->author !== '' ? $bp->author : 'Unknown',
                'authorUri' => $bp->authorUri,
                'pluginUri' => $bp->pluginUri,
                'active' => (bool) ($row['enabled'] ?? false),
                'installed' => $installed,
                'source' => $bp->source,
                'requiresPhp' => $bp->requiresPhp,
                'requiresWp' => $bp->requiresWp,
                'license' => $bp->license,
                'tags' => $bp->tags,
                'updateAvailable' => isset($updates[$slug]),
                'updateVersion' => $updates[$slug] ?? null,
                'category' => $bp->tags[0] ?? 'Uncategorized',
                'entryFile' => $bp->entryFile,
                'path' => $bp->path,
            ];
        }

        // 2) Installed-but-not-on-disk (orphaned) — surface as disabled so
        // the admin can uninstall them cleanly.
        foreach ($installedRows as $slug => $row) {
            if (isset($discovered[$slug])) {
                continue;
            }

            $plugins[] = [
                'id' => $slug,
                'name' => (string) ($row['metadata']['name'] ?? $slug),
                'slug' => $slug,
                'desc' => (string) ($row['metadata']['description'] ?? ''),
                'version' => (string) ($row['version'] ?? '0.0.0'),
                'author' => (string) ($row['metadata']['author'] ?? 'Unknown'),
                'authorUri' => '',
                'pluginUri' => '',
                'active' => false,
                'installed' => true,
                'source' => 'orphaned',
                'requiresPhp' => '',
                'requiresWp' => '',
                'license' => '',
                'tags' => [],
                'updateAvailable' => false,
                'updateVersion' => null,
                'category' => 'Uncategorized',
                'entryFile' => '',
                'path' => '',
            ];
        }

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
        $page = (int) ($_GET['page'] ?? 1);
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

            // In a full implementation, this would download and extract the ZIP.
            // For now, return the download URL for the client to process.
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
        $active = (bool) ($body['active'] ?? false);

        if (empty($plugin)) {
            return Response::json(['error' => 'Plugin name is required'], 400);
        }

        if ($active) {
            $this->plugins->setEnabled($plugin, true);
        } else {
            $this->plugins->setEnabled($plugin, false);
        }

        \PrestoWorld\Core\Cache\CacheInvalidation::onContentChange('plugin-toggle');

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

    /**
     * Force a rediscovery of plugins (useful after manual file drop).
     *
     * POST /api/admin/plugins/refresh
     */
    public function refresh(): Response
    {
        $blueprints = $this->discovery->refresh();

        return Response::json([
            'success' => true,
            'count' => count($blueprints),
            'plugins' => array_map(
                static fn(PluginBlueprint $bp): array => $bp->toArray(),
                $blueprints,
            ),
        ]);
    }

    /**
     * Run a security scan on a single plugin's source.
     *
     * POST /api/admin/plugins/security-scan
     * Body: { slug: string }
     *
     * Returns a list of flags (empty = no obvious issues, NOT "safe").
     */
    public function securityScan(): Response
    {
        $body = json_decode(file_get_contents('php://input'), true);
        $slug = $body['slug'] ?? '';

        if (empty($slug)) {
            return Response::json(['error' => 'Slug is required'], 400);
        }

        $bp = $this->discovery->find($slug);
        if ($bp === null || !$bp->isValid) {
            return Response::json(['error' => "Plugin '{$slug}' not found"], 404);
        }

        // Collect the PHP files for this plugin (top-level only — fast).
        $pluginDir = rtrim($bp->path, '/');
        $phpFiles = [];
        $entries = @scandir($pluginDir);
        if ($entries !== false) {
            foreach ($entries as $entry) {
                if ($entry === '.' || $entry === '..') {
                    continue;
                }
                if (str_ends_with($entry, '.php') && is_file($pluginDir . '/' . $entry)) {
                    $phpFiles[] = $entry;
                }
            }
        }

        $scanner = new SecurityScanner();
        $flags = $scanner->scan($pluginDir, $phpFiles);

        return Response::json([
            'slug' => $slug,
            'files_scanned' => count($phpFiles),
            'flags' => $flags,
            'flag_count' => count($flags),
        ]);
    }
}