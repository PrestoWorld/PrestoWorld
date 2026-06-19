<?php

declare(strict_types=1);

namespace PrestoWorld\Plugin;

use PrestoWorld\Plugin\Exceptions\PluginException;

class PluginManifest
{
    private const VALID_HOOK_TYPES = ['action', 'filter'];

    private const REQUIRED_FIELDS = ['name', 'version', 'title'];

    private const DEFAULTS = [
        'description' => '',
        'icon' => '',
        'min_php' => '^8.1',
        'min_presto' => '^1.0',
        'priority' => 50,
        'enabled' => true,
        'namespace' => '',
        'entry' => 'Plugin.php',
        'provides' => [],
        'requires' => [],
        'hooks' => [
            'register' => [],
            'use' => [],
        ],
        'schema' => null,
        'settings' => [],
        'providers' => [],
        'autoload' => [],
        'keywords' => [],
        'repositories' => [],
    ];

    private array $data;
    private string $path;

    public function __construct(string $pluginPath)
    {
        $this->path = $pluginPath;
        $this->data = $this->load($pluginPath);
    }

    public static function exists(string $pluginPath): bool
    {
        return file_exists($pluginPath . '/plugin.json');
    }

    public static function find(string $directory): array
    {
        $manifests = [];

        if (!is_dir($directory)) {
            return $manifests;
        }

        foreach (scandir($directory) as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $pluginPath = $directory . '/' . $entry;

            if (!is_dir($pluginPath)) {
                continue;
            }

            $manifest = new self($pluginPath);

            if ($manifest->valid()) {
                $manifests[$manifest->name()] = $manifest;
            }
        }

        return $manifests;
    }

    private function load(string $pluginPath): array
    {
        $path = $pluginPath . '/plugin.json';

        if (!file_exists($path)) {
            return [];
        }

        $content = file_get_contents($path);
        $data = json_decode($content, true);

        return is_array($data) ? $data : [];
    }

    public function valid(): bool
    {
        foreach (self::REQUIRED_FIELDS as $field) {
            if (!isset($this->data[$field]) || (is_string($this->data[$field]) && $this->data[$field] === '')) {
                return false;
            }
        }

        return true;
    }

    public function validate(): array
    {
        $errors = [];

        foreach (self::REQUIRED_FIELDS as $field) {
            if (!isset($this->data[$field]) || (is_string($this->data[$field]) && $this->data[$field] === '')) {
                $errors[] = "Missing required field: {$field}";
            }
        }

        $hooks = $this->data['hooks'] ?? [];

        if (isset($hooks['use']) && is_array($hooks['use'])) {
            foreach ($hooks['use'] as $i => $hook) {
                if (!is_string($hook)) {
                    $errors[] = "hooks.use[{$i}]: must be a string";
                }
            }
        }

        if (isset($hooks['register']) && is_array($hooks['register'])) {
            foreach ($hooks['register'] as $i => $hook) {
                if (!is_string($hook)) {
                    $errors[] = "hooks.register[{$i}]: must be a string";
                }
            }
        }

        $requires = $this->data['requires'] ?? [];

        if (is_array($requires)) {
            foreach ($requires as $i => $req) {
                if (is_string($req)) {
                    continue;
                }
                if (is_array($req) && isset($req['name'])) {
                    continue;
                }
                $errors[] = "requires[{$i}]: must be a string (plugin name) or object with 'name' key";
            }
        }

        $schema = $this->data['schema'] ?? null;

        if ($schema !== null && !is_array($schema)) {
            $errors[] = "schema: must be a valid schema object or null";
        }

        return $errors;
    }

    public function name(): string
    {
        return $this->data['name'];
    }

    public function title(): string
    {
        return $this->data['title'];
    }

    public function version(): string
    {
        return $this->data['version'];
    }

    public function description(): string
    {
        return $this->data['description'] ?? '';
    }

    public function icon(): string
    {
        $icon = $this->data['icon'] ?? '';

        if ($icon !== '' && !str_starts_with($icon, '/') && !str_starts_with($icon, 'http')) {
            return $this->path . '/' . $icon;
        }

        return $icon;
    }

    public function minPhp(): string
    {
        return $this->data['min_php'] ?? '^8.1';
    }

    public function minPresto(): string
    {
        return $this->data['min_presto'] ?? '^1.0';
    }

    public function priority(): int
    {
        return (int) ($this->data['priority'] ?? 50);
    }

    public function enabled(): bool
    {
        return $this->data['enabled'] ?? true;
    }

    public function namespace(): string
    {
        return $this->data['namespace'] ?? '';
    }

    public function entry(): string
    {
        return $this->data['entry'] ?? 'Plugin.php';
    }

    public function entryClass(): ?string
    {
        $ns = $this->namespace();

        if ($ns === '') {
            return null;
        }

        $entryFile = $this->entry();
        $className = pathinfo($entryFile, PATHINFO_FILENAME);

        return $ns . '\\' . $className;
    }

    public function provides(): array
    {
        return $this->data['provides'] ?? [];
    }

    public function dependencies(): array
    {
        $requires = $this->data['requires'] ?? [];
        $deps = [];

        foreach ($requires as $req) {
            if (is_string($req)) {
                $deps[$req] = '*';
            } elseif (is_array($req) && isset($req['name'])) {
                $deps[$req['name']] = $req['version'] ?? '*';
            }
        }

        return $deps;
    }

    public function dependencyNames(): array
    {
        return array_keys($this->dependencies());
    }

    public function hooks(): array
    {
        return $this->data['hooks'] ?? [
            'register' => [],
            'use' => [],
        ];
    }

    public function declaredHooks(): array
    {
        return $this->hooks()['register'] ?? [];
    }

    public function usedHooks(): array
    {
        return $this->hooks()['use'] ?? [];
    }

    public function schema(): ?array
    {
        return $this->data['schema'] ?? null;
    }

    public function schemaFile(): ?string
    {
        $schemaPath = $this->path . '/schema.json';

        if (file_exists($schemaPath)) {
            return $schemaPath;
        }

        return null;
    }

    public function settings(): array
    {
        return $this->data['settings'] ?? [];
    }

    public function providers(): array
    {
        return $this->data['providers'] ?? [];
    }

    public function keywords(): array
    {
        return $this->data['keywords'] ?? [];
    }

    public function repositories(): array
    {
        return $this->data['repositories'] ?? [];
    }

    public function path(): string
    {
        return $this->path;
    }

    public function toArray(): array
    {
        return array_merge(self::DEFAULTS, $this->data, [
            '_path' => $this->path,
        ]);
    }

    public static function generateStub(
        string $name,
        string $title,
        string $namespace,
        string $description = '',
    ): string {
        $stub = [
            'name' => $name,
            'title' => $title,
            'version' => '1.0.0',
            'description' => $description ?: "{$title} plugin",
            'min_php' => '^8.1',
            'min_presto' => '^1.0',
            'priority' => 50,
            'enabled' => true,
            'namespace' => $namespace,
            'entry' => 'Plugin.php',
            'provides' => [],
            'requires' => [],
            'hooks' => [
                'register' => [],
                'use' => [],
            ],
            'schema' => null,
            'settings' => [],
            'providers' => [],
            'autoload' => [
                'psr-4' => [
                    $namespace . '\\' => 'src/',
                ],
            ],
            'keywords' => [],
        ];

        return json_encode($stub, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
    }
}
