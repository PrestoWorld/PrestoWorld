<?php

declare(strict_types=1);

namespace PrestoWorld\Core;

/**
 * Nonce — wp_create_nonce/wp_verify_nonce/wp_nonce_field... (spec 10 §10.4 Group security).
 */
final class Nonce
{
    private const ALGO = 'sha256';
    private const LIFETIME = 86400;

    private function __construct()
    {
    }

    public static function create(string $action = '-1'): string
    {
        $userId = AuthService::currentUserId();
        $token = self::sessionToken();
        $tick = (int) ceil(time() / (self::LIFETIME / 2));

        return substr(hash_hmac(self::ALGO, $action . '|' . $tick . '|' . $userId, $token), -12, 10);
    }

    public static function verify(string $nonce, string $action = '-1'): bool
    {
        if ($nonce === '') {
            return false;
        }

        $userId = AuthService::currentUserId();
        $token = self::sessionToken();

        foreach ([0, 1] as $tickOffset) {
            $tick = (int) ceil(time() / (self::LIFETIME / 2)) - $tickOffset;
            $expected = substr(hash_hmac(self::ALGO, $action . '|' . $tick . '|' . $userId, $token), -12, 10);
            if (hash_equals($expected, $nonce)) {
                return true;
            }
        }

        return false;
    }

    public static function field(string $action = '-1', string $name = '_wpnonce', bool $referer = true, bool $echo = true): string
    {
        $field = '<input type="hidden" id="' . $name . '" name="' . $name . '" value="' . self::create($action) . '" />';
        if ($referer) {
            $field .= self::refererField();
        }

        return $field;
    }

    public static function url(string $actionurl, string $action = '-1', string $name = '_wpnonce'): string
    {
        return Url::addQueryArg($name, self::create($action), $actionurl);
    }

    public static function checkAdmin(string $action = '-1', string $queryArg = '_wpnonce'): bool
    {
        $nonce = self::requestValue($queryArg);

        return $nonce !== null && self::verify($nonce, $action);
    }

    public static function checkAjax(string $action = '-1', string|false|null $queryArg = false): bool
    {
        $nonce = self::requestValue($queryArg === false ? '_ajax_nonce' : (string) $queryArg);
        if ($nonce === null || !self::verify($nonce, $action)) {
            return false;
        }

        return true;
    }

    public static function refererField(bool $echo = false): string
    {
        $referer = Request::referer();
        $field = '<input type="hidden" name="_wp_http_referer" value="' . Escape::attr($referer) . '" />';
        if ($echo) {
            echo $field;
            return '';
        }

        return $field;
    }

    private static function requestValue(string $key): ?string
    {
        $value = $_REQUEST[$key] ?? null;

        return is_string($value) ? $value : null;
    }

    private static function sessionToken(): string
    {
        $token = Config::string('session_token', 'pw-token');

        return $token !== '' ? $token : 'pw-token';
    }
}