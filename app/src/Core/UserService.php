<?php

declare(strict_types=1);

namespace PrestoWorld\Core;

use PrestoWorld\Core\Error\PrestoError;
use PrestoWorld\Core\User\UserEntity;

/**
 * UserService — wp_create_user/wp_insert_user/wp_update_user/wp_delete_user.
 */
final class UserService
{
    private function __construct()
    {
    }

    public static function create(string $username, string $password, string $email = ''): int|PrestoError
    {
        if (UserRepository::findBy(['login' => $username]) !== false) {
            return new PrestoError('existing_user_login', __('Sorry, that username already exists!'));
        }

        $user = new UserEntity(
            id: self::nextId(),
            userLogin: $username,
            userPass: password_hash($password, PASSWORD_DEFAULT),
            userNicename: Sanitize::title($username),
            userEmail: $email,
            displayName: $username,
            roles: ['subscriber'],
        );

        UserRepository::save($user);

        return $user->id;
    }

    /**
     * @param array<string, mixed> $userdata
     */
    public static function insert(array $userdata): int|PrestoError
    {
        $username = (string) ($userdata['user_login'] ?? '');
        $password = (string) ($userdata['user_pass'] ?? Security::generatePassword());

        if ($username === '') {
            return new PrestoError('empty_user_login', 'Username cannot be empty.');
        }

        $id = self::create($username, $password, (string) ($userdata['user_email'] ?? ''));
        if ($id instanceof PrestoError) {
            return $id;
        }

        return self::update(array_merge($userdata, ['ID' => $id]));
    }

    /**
     * @param array<string, mixed> $userdata
     */
    public static function update(array $userdata): int|PrestoError
    {
        $id = (int) ($userdata['ID'] ?? $userdata['id'] ?? 0);
        $user = UserRepository::find($id);

        if (!$user instanceof UserEntity) {
            return new PrestoError('invalid_user_id', 'Invalid user ID.');
        }

        if (isset($userdata['user_pass']) && is_string($userdata['user_pass'])) {
            $user->userPass = password_hash($userdata['user_pass'], PASSWORD_DEFAULT);
        }
        if (isset($userdata['user_email']) && is_string($userdata['user_email'])) {
            $user->userEmail = $userdata['user_email'];
        }
        if (isset($userdata['display_name']) && is_string($userdata['display_name'])) {
            $user->displayName = $userdata['display_name'];
        }
        if (isset($userdata['role']) && is_string($userdata['role'])) {
            $user->roles = [$userdata['role']];
        }

        UserRepository::save($user);

        return $user->id;
    }

    public static function delete(int $id, ?int $reassign = null): bool
    {
        return UserRepository::delete($id);
    }

    private static function nextId(): int
    {
        $max = 0;
        foreach (UserRepository::query() as $user) {
            $max = max($max, $user->id);
        }

        return $max + 1;
    }
}