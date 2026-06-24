<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use PrestoWorld\Contracts\Plugin\PluginStoreInterface;
use Witals\Framework\Http\Response;

class PluginsController
{
    public function __construct(
        protected PluginStoreInterface $plugins,
    ) {}

    public function plugins(): Response
    {
        $installed = $this->plugins->getInstalledPlugins();

        $plugins = array_values(array_map(fn(array $row) => [
            'id' => $row['name'],
            'name' => $row['name'],
            'desc' => $row['metadata']['desc'] ?? $row['metadata']['description'] ?? '',
            'version' => $row['version'],
            'author' => $row['metadata']['author'] ?? 'Unknown',
            'active' => $row['enabled'],
            'updateAvailable' => $row['metadata']['update_available'] ?? false,
            'category' => $row['metadata']['category'] ?? 'Uncategorized',
        ], $installed));

        return Response::json($plugins);
    }
}
