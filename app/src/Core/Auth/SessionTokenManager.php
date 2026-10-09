<?php

declare(strict_types=1);

namespace PrestoWorld\Core\Auth;

/**
 * SessionTokenManager — WP_Session_Tokens replacement (spec 10 §10.5.5).
 */
class SessionTokenManager
{
    /** @var array<int, array<string, array<string, mixed>>> */
    private static array $store = [];

    private int $userId;

    public function __construct(int $userId = 0)
    {
        $this->userId = $userId;
    }

    public function getSessionUserId(): int
    {
        return $this->userId;
    }

    /** @return array<string, mixed>|null */
    public function get(string $token): ?array
    {
        $sessions = self::$store[$this->userId] ?? [];
        if (!isset($sessions[$token])) {
            return null;
        }

        $session = $sessions[$token];

        return is_array($session) ? $session : null;
    }

    /** @return array<string, array<string, mixed>> */
    public function getAll(): array
    {
        return self::$store[$this->userId] ?? [];
    }

    public function create(int $expiration): string
    {
        $token = bin2hex(random_bytes(16));
        self::$store[$this->userId][$token] = [
            'expiration' => $expiration,
            'ip' => '',
            'login' => time(),
            'ua' => '',
        ];

        return $token;
    }

    public function update(string $token, int $expiration): bool
    {
        if (!isset(self::$store[$this->userId][$token])) {
            return false;
        }

        self::$store[$this->userId][$token]['expiration'] = $expiration;

        return true;
    }

    public function destroy(string $token): bool
    {
        if (!isset(self::$store[$this->userId][$token])) {
            return false;
        }

        unset(self::$store[$this->userId][$token]);

        return true;
    }

    public function destroyOthers(string $token): int
    {
        $sessions = self::$store[$this->userId] ?? [];
        $removed = 0;
        foreach (array_keys($sessions) as $key) {
            if ($key !== $token) {
                unset(self::$store[$this->userId][$key]);
                $removed++;
            }
        }

        return $removed;
    }

    public function destroyAll(): bool
    {
        unset(self::$store[$this->userId]);

        return true;
    }

    public function verify(string $token): bool
    {
        $session = $this->get($token);
        if ($session === null) {
            return false;
        }

        $expiration = $session['expiration'] ?? 0;

        return is_numeric($expiration) && (int) $expiration >= time();
    }

    public static function reset(): void
    {
        self::$store = [];
    }
}
