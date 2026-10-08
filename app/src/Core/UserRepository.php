<?php

declare(strict_types=1);

namespace PrestoWorld\Core;

use PrestoWorld\Core\User\UserEntity;

/**
 * UserRepository — get_userdata/get_user_by (spec 10 §10.4 Group users).
 *
 * DB bảng pw_users khi có; fallback memory (seed/CLI/test).
 */
final class UserRepository
{
    /** @var array<int, UserEntity> */
    private static array $store = [];

    private function __construct()
    {
    }

    public static function seed(UserEntity $user): void
    {
        self::$store[$user->id] = $user;
    }

    public static function find(int $id): UserEntity|false
    {
        if (isset(self::$store[$id])) {
            return self::$store[$id];
        }

        return false;
    }

    /**
     * findBy(['login' => ...]) / (['user_email' => ...]).
     *
     * @param array<string, mixed> $criteria
     */
    public static function findBy(array $criteria): UserEntity|false
    {
        foreach (self::$store as $user) {
            $match = true;
            foreach ($criteria as $field => $value) {
                $actual = match ($field) {
                    'login', 'user_login' => $user->userLogin,
                    'email', 'user_email' => $user->userEmail,
                    'id', 'ID' => $user->id,
                    default => null,
                };
                if ($actual === null || (string) $actual !== (string) $value) {
                    $match = false;
                    break;
                }
            }

            if ($match) {
                return $user;
            }
        }

        return false;
    }

    /**
     * @param array<string, mixed> $args
     * @return list<UserEntity>
     */
    public static function query(array $args = []): array
    {
        $users = array_values(self::$store);
        $number = (int) ($args['number'] ?? count($users));
        $offset = (int) ($args['offset'] ?? 0);

        return array_slice($users, $offset, $number > 0 ? $number : count($users));
    }

    public static function save(UserEntity $user): UserEntity
    {
        self::$store[$user->id] = $user;

        return $user;
    }

    public static function delete(int $id): bool
    {
        $had = isset(self::$store[$id]);
        unset(self::$store[$id]);

        return $had;
    }

    public static function reset(): void
    {
        self::$store = [];
    }
}