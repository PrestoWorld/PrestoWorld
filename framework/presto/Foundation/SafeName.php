<?php

declare(strict_types=1);

namespace PrestoWorld\Foundation;

class SafeName
{
    private const TABLE_PATTERN = '/^[a-zA-Z_][a-zA-Z0-9_]*$/';
    private const COLUMN_PATTERN = '/^[a-zA-Z_][a-zA-Z0-9_]*$/';
    private const ALIAS_PATTERN = '/^[a-zA-Z_][a-zA-Z0-9_]*$/';

    public static function table(string $name): string
    {
        if (!preg_match(self::TABLE_PATTERN, $name)) {
            throw new \InvalidArgumentException("Invalid table name: '{$name}'");
        }
        return $name;
    }

    public static function column(string $name): string
    {
        if (!preg_match(self::COLUMN_PATTERN, $name)) {
            throw new \InvalidArgumentException("Invalid column name: '{$name}'");
        }
        return $name;
    }

    public static function alias(string $name): string
    {
        if (!preg_match(self::ALIAS_PATTERN, $name)) {
            throw new \InvalidArgumentException("Invalid alias name: '{$name}'");
        }
        return $name;
    }

    public static function prefixed(string $prefix, string $table): string
    {
        $safePrefix = self::table($prefix === '' ? 'default' : $prefix);
        if ($prefix === '') {
            return self::table($table);
        }
        return self::table($safePrefix . $table);
    }
}
