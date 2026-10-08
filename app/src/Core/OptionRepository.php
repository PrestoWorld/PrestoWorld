<?php

declare(strict_types=1);

namespace PrestoWorld\Core;

use Cycle\Database\DatabaseInterface;

/**
 * OptionRepository — get_option/update_option/... (spec 10 §10.4 Group options).
 *
 * Backend: bảng pw_options qua Cycle DB; fallback in-memory (CLI/test/worker).
 * Cache theo request: đã get thì giữ trong memory, LegacyState::reset() xoá.
 */
final class OptionRepository
{
    private const TABLE = 'pw_options';

    /** @var array<string, mixed> */
    private static array $memory = [];

    /** @var array<string, bool> */
    private static array $networkMemory = [];

    private static ?DatabaseInterface $db = null;

    private function __construct()
    {
    }

    public static function setDatabase(?DatabaseInterface $db): void
    {
        self::$db = $db;
    }

    public static function get(string $name, mixed $default = null): mixed
    {
        if (array_key_exists($name, self::$memory)) {
            return self::$memory[$name];
        }

        $db = self::database();
        if ($db !== null) {
            $value = self::fetchFromDb($db, self::TABLE, $name);
            if ($value !== null) {
                self::$memory[$name] = $value;
                return $value;
            }
        }

        return $default;
    }

    public static function add(string $name, mixed $value = ''): bool
    {
        if (self::has($name)) {
            return false;
        }

        return self::update($name, $value);
    }

    public static function update(string $name, mixed $value): bool
    {
        self::$memory[$name] = $value;

        $db = self::database();
        if ($db !== null) {
            self::writeToDb($db, self::TABLE, $name, $value);
        }

        return true;
    }

    public static function delete(string $name): bool
    {
        $had = array_key_exists($name, self::$memory) || self::has($name);
        unset(self::$memory[$name]);

        $db = self::database();
        if ($db !== null && $db->hasTable(self::TABLE)) {
            $db->delete(self::TABLE, ['option_name' => $name])->run();
        }

        return $had;
    }

    public static function has(string $name): bool
    {
        if (array_key_exists($name, self::$memory)) {
            return true;
        }

        $db = self::database();

        return $db !== null && self::fetchFromDb($db, self::TABLE, $name) !== null;
    }

    /**
     * loadAll — get_all_options (mảng đã load).
     *
     * @return array<string, mixed>
     */
    public static function loadAll(bool $autoloadOnly = false): array
    {
        $db = self::database();
        if ($db !== null && $db->hasTable(self::TABLE)) {
            $query = $db->select('*')->from(self::TABLE);
            if ($autoloadOnly) {
                $query->where('autoload', 'yes');
            }

            /** @var list<array<string, mixed>> $rows */
            $rows = $query->fetchAll();
            foreach ($rows as $row) {
                $name = is_scalar($row['option_name'] ?? null) ? (string) $row['option_name'] : '';
                if ($name === '') {
                    continue;
                }
                $raw = $row['option_value'] ?? null;
                self::$memory[$name] = self::decode($raw);
            }
        }

        return self::$memory;
    }

    public static function getNetwork(string $name, mixed $default = null): mixed
    {
        if (array_key_exists($name, self::$networkMemory)) {
            return self::$networkMemory[$name];
        }

        return $default;
    }

    public static function updateNetwork(string $name, mixed $value): bool
    {
        self::$networkMemory[$name] = $value;

        return true;
    }

    public static function deleteNetwork(string $name): bool
    {
        $had = array_key_exists($name, self::$networkMemory);
        unset(self::$networkMemory[$name]);

        return $had;
    }

    public static function reset(): void
    {
        self::$memory = [];
        self::$networkMemory = [];
    }

    private static function database(): ?DatabaseInterface
    {
        if (self::$db !== null) {
            return self::$db;
        }

        self::$db = \PrestoWorld\Core\Support\App::make(DatabaseInterface::class, null);

        return self::$db;
    }

    private static function fetchFromDb(DatabaseInterface $db, string $table, string $name): mixed
    {
        if (!$db->hasTable($table)) {
            return null;
        }

        try {
            /** @var array<string, mixed>|false $row */
            $row = $db->select('option_value')->from($table)->where('option_name', $name)->run()->fetch();
        } catch (\Throwable) {
            return null;
        }

        if (!is_array($row) || !array_key_exists('option_value', $row)) {
            return null;
        }

        return self::decode($row['option_value']);
    }

    private static function writeToDb(DatabaseInterface $db, string $table, string $name, mixed $value): void
    {
        if (!$db->hasTable($table)) {
            return;
        }

        try {
            $encoded = self::encode($value);

            /** @var array<string, mixed>|false $existing */
            $existing = $db->select('id')->from($table)->where('option_name', $name)->run()->fetch();

            if (is_array($existing) && $existing !== []) {
                $db->update($table, ['option_value' => $encoded], ['id' => $existing['id']])->run();
            } else {
                $db->insert($table)->values(['option_name' => $name, 'option_value' => $encoded])->run();
            }
        } catch (\Throwable) {
            // DB không sẵn sàng — memory vẫn giữ giá trị.
        }
    }

    private static function encode(mixed $value): string
    {
        if (is_string($value)) {
            return $value;
        }

        return (string) json_encode($value, JSON_UNESCAPED_UNICODE);
    }

    private static function decode(mixed $raw): mixed
    {
        if (!is_string($raw)) {
            return $raw;
        }

        $decoded = json_decode($raw, true);

        return json_last_error() === JSON_ERROR_NONE ? $decoded : $raw;
    }
}