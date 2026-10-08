<?php

declare(strict_types=1);

namespace PrestoWorld\Core;

/**
 * Security — wp_rand/wp_generate_password/wp_referer_field... (spec 10 §10.4).
 */
final class Security
{
    private function __construct()
    {
    }

    public static function rand(int $min = 0, int $max = PHP_INT_MAX): int
    {
        return random_int($min, $max);
    }

    public static function generatePassword(int $length = 12, bool $specialChars = true, bool $extraSpecialChars = false): string
    {
        $chars = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        if ($specialChars) {
            $chars .= '!@#$%^&*()-_=+[]{};:,.?';
        }
        if ($extraSpecialChars) {
            $chars .= '-=+[]{};:,.?"|`~';
        }

        $password = '';
        for ($i = 0; $i < $length; $i++) {
            $password .= $chars[random_int(0, strlen($chars) - 1)];
        }

        return $password;
    }

    public static function refererField(bool $echo = false): string
    {
        return Nonce::refererField($echo);
    }

    public static function randomString(int $length = 64): string
    {
        $bytes = random_bytes(max(1, (int) ceil($length / 2)));

        return substr(bin2hex($bytes), 0, $length);
    }

    /**
     * @return list<string>
     */
    public static function allowedProtocols(): array
    {
        return ['http', 'https', 'ftp', 'ftps', 'mailto', 'news', 'irc', 'tel'];
    }
}