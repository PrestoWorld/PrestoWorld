<?php

declare(strict_types=1);

namespace PrestoWorld\Core\User;

use PrestoWorld\Core\UserRepository;
use PrestoWorld\Core\User\UserEntity;

/**
 * UserQuery — WP_User_Query replacement (spec 10 §10.5.3/§10.5.5).
 */
class UserQuery
{
    /** @var array<string, mixed> */
    private array $args;

    /** @var list<UserEntity> */
    private array $results = [];

    private bool $ran = false;

    /** @param array<string, mixed> $args */
    public function __construct(array $args = [])
    {
        $this->args = $args;
    }

    /** @return array<string, mixed> */
    public function getArgs(): array
    {
        return $this->args;
    }

    /** @return list<UserEntity> */
    public function users(): array
    {
        if ($this->ran) {
            return $this->results;
        }

        $queryArgs = $this->args;
        unset($queryArgs['number']);
        $users = UserRepository::query($queryArgs);

        $search = $this->args['search'] ?? $this->args['s'] ?? '';
        if (is_string($search) && $search !== '') {
            $needle = strtolower($search);
            $users = array_values(array_filter(
                $users,
                static fn (UserEntity $user): bool => str_contains(strtolower($user->userLogin), $needle)
                    || str_contains(strtolower($user->displayName), $needle)
                    || str_contains(strtolower($user->userEmail), $needle),
            ));
        }

        $role = $this->args['role'] ?? '';
        if (is_string($role) && $role !== '') {
            $users = array_values(array_filter(
                $users,
                static fn (UserEntity $user): bool => in_array($role, $user->roles, true),
            ));
        }

        $number = $this->args['number'] ?? 0;
        if (is_numeric($number) && (int) $number > 0) {
            $users = array_slice($users, 0, (int) $number);
        }

        $this->results = $users;
        $this->ran = true;

        return $this->results;
    }

    public function count(): int
    {
        return count($this->users());
    }

    public function hasUsers(): bool
    {
        return $this->users() !== [];
    }
}
