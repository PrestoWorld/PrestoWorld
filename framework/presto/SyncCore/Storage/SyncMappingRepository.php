<?php

declare(strict_types=1);

namespace PrestoWorld\Core\SyncCore\Storage;

use Cycle\Database\DatabaseInterface;

final class SyncMappingRepository
{
    public function __construct(
        private DatabaseInterface $db,
        private string $prefix = 'pw_',
    ) {
    }

    /**
     * @param array<string, mixed> $meta
     */
    public function upsert(
        string $platform,
        string $resource,
        string $externalId,
        string $localId,
        string $businessId = '',
        array $meta = [],
    ): void {
        if ($externalId === '' || $localId === '') {
            return;
        }

        $now = time();
        $table = $this->table('sync_mappings');
        $existing = $this->db->select('id')->from($table)
            ->where('platform', $platform)
            ->where('resource', $resource)
            ->where('external_id', $externalId)
            ->run()->fetch();

        $values = [
            'platform' => $platform,
            'resource' => $resource,
            'business_id' => $businessId,
            'external_id' => substr($externalId, 0, 96),
            'local_id' => substr($localId, 0, 96),
            'meta' => $meta === [] ? null : json_encode($meta, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'updated_at' => $now,
        ];

        if ($existing === false) {
            $values['created_at'] = $now;
            $this->db->insert($table)->values($values)->run();

            return;
        }

        $this->db->update($table, $values, ['id' => (int) ((array) $existing)['id']])->run();
    }

    public function find(string $platform, string $resource, string $externalId): ?array
    {
        $row = $this->db->select('*')->from($this->table('sync_mappings'))
            ->where('platform', $platform)
            ->where('resource', $resource)
            ->where('external_id', $externalId)
            ->run()->fetch();

        return $row === false ? null : $this->decode((array) $row);
    }

    /**
     * @param array<string, mixed> $filters
     *
     * @return array{items: list<array<string, mixed>>, total: int}
     */
    public function paginate(array $filters, int $page, int $perPage): array
    {
        $query = $this->db->select('*')->from($this->table('sync_mappings'));
        $count = $this->db->select('*')->from($this->table('sync_mappings'));

        foreach (['platform', 'resource'] as $column) {
            $value = trim((string) ($filters[$column] ?? ''));
            if ($value !== '') {
                $query->where($column, $value);
                $count->where($column, $value);
            }
        }

        $keyword = trim((string) ($filters['q'] ?? ''));
        if ($keyword !== '') {
            $query->where(fn ($q) => $q
                ->where('external_id', 'LIKE', '%' . $keyword . '%')
                ->orWhere('local_id', 'LIKE', '%' . $keyword . '%'));
            $count->where(fn ($q) => $q
                ->where('external_id', 'LIKE', '%' . $keyword . '%')
                ->orWhere('local_id', 'LIKE', '%' . $keyword . '%'));
        }

        $rows = $query->orderBy('id', 'DESC')
            ->offset(max(0, ($page - 1) * $perPage))
            ->limit($perPage)
            ->run()->fetchAll();

        return [
            'items' => array_map(fn ($row) => $this->decode((array) $row), $rows),
            'total' => (int) $count->count(),
        ];
    }

    public function delete(int $id): bool
    {
        $deleted = $this->db->delete($this->table('sync_mappings'), ['id' => $id])->run();

        return $deleted > 0;
    }

    /**
     * @param array<string, mixed> $row
     *
     * @return array<string, mixed>
     */
    private function decode(array $row): array
    {
        $row['meta'] = is_string($row['meta'] ?? null) ? json_decode($row['meta'], true) : null;

        return $row;
    }

    private function table(string $name): string
    {
        return $this->prefix . $name;
    }
}
