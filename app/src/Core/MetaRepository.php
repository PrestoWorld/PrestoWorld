<?php

declare(strict_types=1);

namespace PrestoWorld\Core;

/**
 * MetaRepository — get/add/update/delete metadata cho post/user/comment/term
 * (spec 10 §10.4.16). Giá trị lưu dạng WP: mỗi key là một list giá trị.
 */
final class MetaRepository
{
    /** @var array<string, array<int, array<string, list<mixed>>>> */
    private static array $store = [];

    /** @var array<string, array<string, array<string, mixed>>> */
    private static array $registered = [];

    private function __construct()
    {
    }

    /**
     * @param array<string, mixed> $args
     */
    public static function register(string $metaType, string $key, array $args = []): void
    {
        self::$registered[$metaType][$key] = $args;
    }

    public static function get(string $metaType, int $objectId, string $key = '', bool $single = false): mixed
    {
        $meta = self::$store[$metaType][$objectId] ?? [];

        if ($key === '') {
            return $meta;
        }

        $values = $meta[$key] ?? [];
        if ($single) {
            return $values[0] ?? '';
        }

        return $values;
    }

    public static function add(string $metaType, int $objectId, string $key, mixed $value, bool $unique = false): bool
    {
        if ($unique && self::exists($metaType, $objectId, $key)) {
            return false;
        }

        self::$store[$metaType][$objectId][$key][] = $value;

        return true;
    }

    public static function update(string $metaType, int $objectId, string $key, mixed $value, mixed $prevValue = ''): bool
    {
        self::$store[$metaType][$objectId][$key] = [$value];

        return true;
    }

    public static function delete(string $metaType, int $objectId, string $key, mixed $value = '', bool $deleteAll = false): bool
    {
        if (!isset(self::$store[$metaType][$objectId][$key])) {
            return false;
        }

        if ($value === '' || $deleteAll) {
            unset(self::$store[$metaType][$objectId][$key]);

            return true;
        }

        $remaining = array_values(array_filter(
            self::$store[$metaType][$objectId][$key],
            static fn (mixed $existing): bool => $existing !== $value,
        ));

        if ($remaining === []) {
            unset(self::$store[$metaType][$objectId][$key]);
        } else {
            self::$store[$metaType][$objectId][$key] = $remaining;
        }

        return true;
    }

    public static function exists(string $metaType, int $objectId, string $key): bool
    {
        return isset(self::$store[$metaType][$objectId][$key]);
    }

    /**
     * get_post_custom — trả về map key => list giá trị.
     *
     * @return array<string, list<mixed>>
     */
    public static function all(int $postId): array
    {
        return self::$store['post'][$postId] ?? [];
    }

    /**
     * @return list<string>
     */
    public static function keys(int $postId): array
    {
        return array_keys(self::all($postId));
    }

    /**
     * @return list<mixed>
     */
    public static function values(int $postId, string $key): array
    {
        return self::$store['post'][$postId][$key] ?? [];
    }

    public static function getGeneric(string $metaType, int $objectId, string $key = '', bool $single = false): mixed
    {
        return self::get($metaType, $objectId, $key, $single);
    }

    public static function updateGeneric(string $metaType, int $objectId, string $key, mixed $value, mixed $prevValue = ''): bool
    {
        return self::update($metaType, $objectId, $key, $value, $prevValue);
    }

    public static function deleteGeneric(string $metaType, int $objectId, string $key, mixed $value = '', bool $deleteAll = false): bool
    {
        return self::delete($metaType, $objectId, $key, $value, $deleteAll);
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function registered(string $metaType): array
    {
        return self::$registered[$metaType] ?? [];
    }

    public static function reset(): void
    {
        self::$store = [];
        self::$registered = [];
    }
}
