<?php

declare(strict_types=1);

namespace PrestoWorld\Core\SyncCore\Storage;

use Cycle\Database\DatabaseInterface;
use PrestoWorld\Core\SyncCore\Domain\Dto\SyncSettings;

final class SyncSettingsRepository
{
    public function __construct(
        private DatabaseInterface $db,
        private string $prefix = 'pw_',
    ) {
    }

    /**
     * @param array<string, mixed> $defaults
     */
    public function get(string $platform, array $defaults = []): SyncSettings
    {
        $row = $this->db->select('*')->from($this->table('sync_settings'))
            ->where('platform', $platform)
            ->run()->fetch();

        if ($row === false) {
            return new SyncSettings(
                platform: $platform,
                inboundEnabled: (bool) ($defaults['inbound_enabled'] ?? true),
                outboundEnabled: (bool) ($defaults['outbound_enabled'] ?? false),
                data: is_array($defaults['settings'] ?? null) ? $defaults['settings'] : [],
            );
        }

        $row = (array) $row;
        $settings = is_string($row['settings'] ?? null) ? json_decode($row['settings'], true) : null;
        $settings = is_array($settings) ? $settings : [];
        if ($defaults !== []) {
            $settings = $this->mergeDefaults($settings, is_array($defaults['settings'] ?? null) ? $defaults['settings'] : []);
        }

        return new SyncSettings(
            platform: $platform,
            inboundEnabled: (bool) ($row['inbound_enabled'] ?? true),
            outboundEnabled: (bool) ($row['outbound_enabled'] ?? false),
            data: $settings,
        );
    }

    public function save(SyncSettings $settings): SyncSettings
    {
        $now = time();
        $values = [
            'platform' => $settings->platform,
            'inbound_enabled' => $settings->inboundEnabled ? 1 : 0,
            'outbound_enabled' => $settings->outboundEnabled ? 1 : 0,
            'settings' => json_encode($settings->data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'updated_at' => $now,
        ];

        $existing = $this->db->select('id')->from($this->table('sync_settings'))
            ->where('platform', $settings->platform)
            ->run()->fetch();

        if ($existing === false) {
            $values['created_at'] = $now;
            $this->db->insert($this->table('sync_settings'))->values($values)->run();
        } else {
            $this->db->update($this->table('sync_settings'), $values, [
                'id' => (int) ((array) $existing)['id'],
            ])->run();
        }

        return $settings;
    }

    /**
     * @param array<string, mixed> $settings
     * @param array<string, mixed> $defaults
     *
     * @return array<string, mixed>
     */
    private function mergeDefaults(array $settings, array $defaults): array
    {
        foreach ($defaults as $key => $value) {
            if (!array_key_exists($key, $settings)) {
                $settings[$key] = $value;
                continue;
            }
            if (is_array($value) && is_array($settings[$key])) {
                $settings[$key] = $this->mergeDefaults($settings[$key], $value);
            }
        }

        return $settings;
    }

    private function table(string $name): string
    {
        return $this->prefix . $name;
    }
}
