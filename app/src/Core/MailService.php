<?php

declare(strict_types=1);

namespace PrestoWorld\Core;

/**
 * MailService — wp_mail (spec 10 §10.4 Group mail).
 *
 * Runtime mặc định ghi log (không gửi thật) trừ khi SMTP transport được bind
 * qua setTransport(). Đủ để shim wp_mail không fatal.
 */
final class MailService
{
    /** @var array<int, array{to: list<string>, subject: string, message: string, headers: string}> */
    private static array $sent = [];

    /** @var null|callable */
    private static $transport = null;

    private function __construct()
    {
    }

    /**
     * @param list<string> $to
     */
    public static function send(array $to, string $subject, string $message, string $headers = ''): bool
    {
        if ($to === []) {
            return false;
        }

        self::$sent[] = [
            'to' => $to,
            'subject' => $subject,
            'message' => $message,
            'headers' => $headers,
        ];

        if (self::$transport !== null) {
            try {
                $result = call_user_func(self::$transport, $to, $subject, $message, $headers);

                return (bool) $result;
            } catch (\Throwable) {
                return false;
            }
        }

        return true;
    }

    public static function setTransport(?callable $transport): void
    {
        self::$transport = $transport;
    }

    /** @return array<int, array{to: list<string>, subject: string, message: string, headers: string}> */
    public static function sent(): array
    {
        return self::$sent;
    }

    public static function reset(): void
    {
        self::$sent = [];
        self::$transport = null;
    }
}