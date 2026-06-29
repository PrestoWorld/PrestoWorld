<?php

declare(strict_types=1);

namespace PrestoWorld\Foundation\Database;

use Cycle\Database\DatabaseInterface;

class OptionRepository
{
    protected DatabaseInterface $db;
    /** @var non-empty-string */
    protected string $table = 'pw_options';

    public function __construct(DatabaseInterface $db)
    {
        $this->db = $db;
    }

    public function get(string $name, mixed $default = null): mixed
    {
        if ($this->table === '' || !$this->db->hasTable($this->table)) {
            return $default;
        }

        /** @var array<string, mixed>|false $row */
        $row = $this->db->select('option_value')
            ->from($this->table)
            ->where('option_name', $name)
            ->run()
            ->fetch();

        if (!is_array($row) || $row === []) {
            return $default;
        }

        $value = $row['option_value'] ?? null;
        if ($value === null || $value === '') {
            return $default;
        }

        if (!is_string($value)) {
            return $value;
        }

        $decoded = json_decode($value, true);
        return json_last_error() === JSON_ERROR_NONE ? $decoded : $value;
    }

    public function set(string $name, mixed $value): void
    {
        if ($this->table === '' || !$this->db->hasTable($this->table)) {
            return;
        }

        $encoded = is_string($value) ? $value : json_encode($value);

        /** @var array{id: int|string}|false $existing */
        $existing = $this->db->select('id')
            ->from($this->table)
            ->where('option_name', $name)
            ->run()
            ->fetch();

        if (is_array($existing) && $existing !== []) {
            $this->db->update($this->table, ['option_value' => $encoded], ['id' => $existing['id']])->run();
        } else {
            $this->db->insert($this->table)->values([
                'option_name' => $name,
                'option_value' => $encoded,
            ])->run();
        }
    }

    public function delete(string $name): void
    {
        if ($this->table === '' || !$this->db->hasTable($this->table)) {
            return;
        }

        $this->db->delete($this->table, ['option_name' => $name])->run();
    }

    /** @return array<string, mixed> */
    public function all(bool $autoloadOnly = false): array
    {
        if ($this->table === '' || !$this->db->hasTable($this->table)) {
            return [];
        }

        $query = $this->db->select('*')->from($this->table);
        if ($autoloadOnly) {
            $query->where('autoload', 'yes');
        }

        /** @var array<int, array<string, mixed>> $rows */
        $rows = $query->fetchAll();
        $result = [];
        foreach ($rows as $row) {
            $name = is_scalar($row['option_name'] ?? null) ? (string) $row['option_name'] : '';
            $value = $row['option_value'] ?? '';
            if (is_string($value)) {
                $decoded = json_decode($value, true);
                $result[$name] = json_last_error() === JSON_ERROR_NONE ? $decoded : $value;
            } else {
                $result[$name] = $value;
            }
        }
        return $result;
    }

    public function has(string $name): bool
    {
        if ($this->table === '' || !$this->db->hasTable($this->table)) {
            return false;
        }

        return (bool) $this->db->select('id')
            ->from($this->table)
            ->where('option_name', $name)
            ->run()
            ->fetch();
    }
}
