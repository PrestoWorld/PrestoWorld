<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Plugin;
use App\Models\Theme;
use App\Models\Module;
use Cycle\ORM\EntityManagerInterface;
use Cycle\ORM\ORMInterface;

class ComponentScannerService
{
    private EntityManagerInterface $entityManager;
    private ORMInterface $orm;

    public function __construct(EntityManagerInterface $entityManager, ORMInterface $orm)
    {
        $this->entityManager = $entityManager;
        $this->orm = $orm;
    }

    public function scan(bool $force = false): array
    {
        $cacheFile = storage_path('framework/cache/scan_results.php');
        
        if (!$force && file_exists($cacheFile)) {
            return require $cacheFile;
        }

        $results = [
            'mu-plugins' => $this->scanMuPlugins(),
            'plugins' => $this->scanPlugins(),
            'themes' => $this->scanThemes(),
            'modules' => $this->scanModules(),
            'scanned_at' => date('Y-m-d H:i:s')
        ];

        $this->entityManager->run();

        // Save to cache
        $content = "<?php\n\nreturn " . var_export($results, true) . ";\n";
        file_put_contents($cacheFile, $content);

        return $results;
    }

    private function scanMuPlugins(): array
    {
        $path = base_path('mu-plugins');
        if (!is_dir($path)) return [];

        $repo = $this->orm->getRepository(Plugin::class);
        $scanned = [];

        foreach (scandir($path) as $file) {
            if ($file === '.' || $file === '..') continue;
            
            $fullPath = $path . '/' . $file;
            if (is_dir($fullPath)) continue;
            if (!str_ends_with($file, '.php')) continue;

            $isWordpress = true; // MU-plugins are inherently WordPress patterns
            $metadata = ['path' => $fullPath, 'type' => 'mu-plugin'];

            $content = file_get_contents($fullPath);
            if (preg_match('/Plugin Name:\s*(.*)$/m', $content, $matches)) {
                $metadata['plugin_name'] = trim($matches[1]);
            }

            $plugin = $repo->findOne(['path' => 'mu/' . $file]) ?? new Plugin();
            $plugin->path = 'mu/' . $file;
            $plugin->name = $metadata['plugin_name'] ?? $file;
            $plugin->is_wordpress = $isWordpress;
            $plugin->metadata = $metadata;

            $this->entityManager->persist($plugin);
            $scanned[] = [
                'name' => $plugin->name,
                'is_wordpress' => $isWordpress,
                'type' => 'mu-plugin'
            ];
        }

        return $scanned;
    }

    private function scanPlugins(): array
    {
        $path = base_path('plugins');
        if (!is_dir($path)) return [];

        $repo = $this->orm->getRepository(Plugin::class);
        $scanned = [];

        foreach (scandir($path) as $dir) {
            if ($dir === '.' || $dir === '..') continue;
            
            $fullPath = $path . '/' . $dir;
            if (!is_dir($fullPath)) continue;

            $isWordpress = !file_exists($fullPath . '/bootstrap.php');
            $metadata = ['path' => $fullPath, 'type' => $isWordpress ? 'wordpress' : 'native'];

            if ($isWordpress) {
                // WP Plugin detections
                $mainFiles = glob($fullPath . '/*.php');
                foreach ($mainFiles as $file) {
                    $content = file_get_contents($file);
                    if (preg_match('/Plugin Name:\s*(.*)$/m', $content, $matches)) {
                        $metadata['plugin_name'] = trim($matches[1]);
                        break;
                    }
                }
            }

            $plugin = $repo->findOne(['path' => $dir]) ?? new Plugin();
            $plugin->path = $dir;
            $plugin->name = $metadata['plugin_name'] ?? $dir;
            $plugin->is_wordpress = $isWordpress;
            $plugin->metadata = $metadata;

            $this->entityManager->persist($plugin);
            $scanned[] = [
                'name' => $plugin->name,
                'is_wordpress' => $isWordpress,
                'type' => $metadata['type']
            ];
        }

        return $scanned;
    }

    private function scanThemes(): array
    {
        $path = base_path('themes');
        if (!is_dir($path)) return [];

        $repo = $this->orm->getRepository(Theme::class);
        $scanned = [];

        foreach (scandir($path) as $dir) {
            if ($dir === '.' || $dir === '..') continue;

            $fullPath = $path . '/' . $dir;
            if (!is_dir($fullPath)) continue;

            $isWordpress = true;
            $metadata = ['path' => $fullPath];

            if (file_exists($fullPath . '/theme.json')) {
                $config = json_decode(file_get_contents($fullPath . '/theme.json'), true);
                if (isset($config['engine']) && $config['engine'] === 'prestoworld') {
                    $isWordpress = false;
                    $metadata['type'] = 'native';
                    $metadata['theme_name'] = $config['name'] ?? $dir;
                }
            }

            if ($isWordpress) {
                $metadata['type'] = 'wordpress';
                if (file_exists($fullPath . '/style.css')) {
                    $content = file_get_contents($fullPath . '/style.css');
                    if (preg_match('/Theme Name:\s*(.*)$/m', $content, $matches)) {
                        $metadata['theme_name'] = trim($matches[1]);
                    }
                }
            }

            $theme = $repo->findOne(['path' => $dir]) ?? new Theme();
            $theme->path = $dir;
            $theme->name = $metadata['theme_name'] ?? $dir;
            $theme->is_wordpress = $isWordpress;
            $theme->metadata = $metadata;

            $this->entityManager->persist($theme);
            $scanned[] = [
                'name' => $theme->name,
                'is_wordpress' => $isWordpress,
                'type' => $metadata['type'] ?? 'wordpress'
            ];
        }

        return $scanned;
    }

    private function scanModules(): array
    {
        $path = base_path('modules');
        if (!is_dir($path)) return [];

        $repo = $this->orm->getRepository(Module::class);
        $scanned = [];

        foreach (scandir($path) as $dir) {
            if ($dir === '.' || $dir === '..') continue;
            
            $fullPath = $path . '/' . $dir;
            if (!is_dir($fullPath)) continue;

            // Modules in PrestoWorld are always native
            $isWordpress = false;
            $metadata = ['path' => $fullPath, 'type' => 'native'];

            $module = $repo->findOne(['path' => $dir]) ?? new Module();
            $module->path = $dir;
            $module->name = $dir;
            $module->is_wordpress = $isWordpress;
            $module->metadata = $metadata;

            $this->entityManager->persist($module);
            $scanned[] = [
                'name' => $module->name,
                'is_wordpress' => $isWordpress,
                'type' => 'native'
            ];
        }

        return $scanned;
    }
}
