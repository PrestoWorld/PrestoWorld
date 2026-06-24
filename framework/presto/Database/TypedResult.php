<?php

declare(strict_types=1);

namespace PrestoWorld\Database;

class TypedResult
{
    public static function int(mixed $value, int $default = 0): int
    {
        if ($value === null || $value === false) {
            return $default;
        }
        return (int) $value;
    }

    public static function string(mixed $value, string $default = ''): string
    {
        if ($value === null || $value === false) {
            return $default;
        }
        return (string) $value;
    }

    public static function float(mixed $value, float $default = 0.0): float
    {
        if ($value === null || $value === false) {
            return $default;
        }
        return (float) $value;
    }

    public static function bool(mixed $value, bool $default = false): bool
    {
        if ($value === null || $value === false) {
            return $default;
        }
        return (bool) $value;
    }

    public static function date(mixed $value, string $format = 'Y-m-d H:i:s', string $default = ''): string
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format($format);
        }
        if (is_string($value) && $value !== '') {
            $ts = strtotime($value);
            if ($ts !== false) {
                return date($format, $ts);
            }
        }
        return $default;
    }

    public static function array(mixed $value, array $default = []): array
    {
        if (!is_array($value)) {
            return $default;
        }
        return $value;
    }

    public static function row(array $row, string $key, mixed $default = null): mixed
    {
        return $row[$key] ?? $default;
    }
}
