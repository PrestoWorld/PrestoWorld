<?php

declare(strict_types=1);

namespace PrestoWorld\Plugin;

use PrestoWorld\Contracts\Plugin\PluginStoreInterface;
use Cycle\Database\DatabaseProviderInterface;

class PluginStore implements PluginStoreInterface
{
    private const TABLE = 'plugin_registry';
    private ?array $cache = null;

    public function __construct(
        private DatabaseProviderInterface $dbal,
    ) {
        $this->ensureTable();
    }

    public function isInstalled(string $pluginName): bool
    {
        $this->loadCache();

        return isset($this->cache[$pluginName]);
    }

    public function getInstalledVersion(string $pluginName): ?string
    {
        $this->loadCache();

        return $this->cache[$pluginName]['version'] ?? null;
    }

    public function markInstalled(string $pluginName, string $version, array $metadata): void
    {
        $db = $this->dbal->database();
        $now = (new \DateTimeImmutable())->format('Y-m-d H:i:s');

        if ($this->isInstalled($pluginName)) {
            $db->update(self::TABLE, [
                'version' => $version,
                'metadata' => json_encode($metadata),
                'enabled' => true,
                'updated_at' => $now,
            ], ['name' => $pluginName]);
        } else {
            $db->insert(self::TABLE)->values([
                'name' => $pluginName,
                'version' => $version,
                'metadata' => json_encode($metadata),
                'enabled' => true,
                'schema_hash' => '',
                'installed_at' => $now,
                'updated_at' => $now,
            ])->run();
        }

        $this->cache[$pluginName] = [
            'name' => $pluginName,
            'version' => $version,
            'enabled' => true,
        ];
    }

    public function markUninstalled(string $pluginName): void
    {
        $this->dbal->database()
            ->delete(self::TABLE)
            ->where('name', $pluginName)
            ->run();

        unset($this->cache[$pluginName]);
    }

    public function setEnabled(string $pluginName, bool $enabled): void
    {
        $this->dbal->database()
            ->update(self::TABLE, [
                'enabled' => $enabled,
            ], ['name' => $pluginName]);

        if (isset($this->cache[$pluginName])) {
            $this->cache[$pluginName]['enabled'] = $enabled;
        }
    }

    public function isEnabled(string $pluginName): bool
    {
        $this->loadCache();

        return $this->cache[$pluginName]['enabled'] ?? false;
    }

    public function getInstalledPlugins(): array
    {
        $this->loadCache();

        return $this->cache;
    }

    public function getSchemaHash(string $pluginName): ?string
    {
        $this->loadCache();

        return $this->cache[$pluginName]['schema_hash'] ?? null;
    }

    public function setSchemaHash(string $pluginName, string $hash): void
    {
        $this->dbal->database()
            ->update(self::TABLE, [
                'schema_hash' => $hash,
            ], ['name' => $pluginName]);

        if (isset($this->cache[$pluginName])) {
            $this->cache[$pluginName]['schema_hash'] = $hash;
        }
    }

    private function loadCache(): void
    {
        if ($this->cache !== null) {
            return;
        }

        $this->cache = [];

        try {
            $rows = $this->dbal->database()
                ->select('name', 'version', 'enabled', 'schema_hash', 'metadata')
                ->from(self::TABLE)
                ->fetchAll();

            foreach ($rows as $row) {
                $this->cache[$row['name']] = [
                    'name' => $row['name'],
                    'version' => $row['version'],
                    'enabled' => (bool) $row['enabled'],
                    'schema_hash' => $row['schema_hash'],
                    'metadata' => json_decode($row['metadata'] ?? '{}', true) ?: [],
                ];
            }
        } catch (\Throwable) {
        }
    }

    private function ensureTable(): void
    {
        try {
            $db = $this->dbal->database();
            $table = $db->table(self::TABLE)->getSchema();

            if ($table->exists()) {
                return;
            }

            $table->column('id')->primary();
            $table->column('name')->string(191)->notNull();
            $table->column('version')->string(20)->notNull();
            $table->column('metadata')->longText()->nullable();
            $table->column('enabled')->boolean()->defaultValue(true);
            $table->column('schema_hash')->string(64)->defaultValue('');
            $table->column('installed_at')->datetime()->notNull();
            $table->column('updated_at')->datetime()->notNull();
            $table->index(['name'])->unique();
            $table->save();
        } catch (\Throwable $e) {
            throw new \RuntimeException(
                "Failed to create plugin_registry table: {$e->getMessage()}",
                0,
                $e,
            );
        }
    }
}
