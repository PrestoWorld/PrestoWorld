<?php

declare(strict_types=1);

namespace PrestoWorld\Plugin;

class VersionConstraint
{
    public static function satisfies(string $version, string $constraint): bool
    {
        if ($constraint === '*' || $constraint === '') {
            return true;
        }

        $version = ltrim($version, 'v');
        $constraint = trim($constraint);

        $parts = preg_split('/\s*\|\|\s*/', $constraint);

        foreach ($parts as $part) {
            if (self::satisfiesSingle($version, $part)) {
                return true;
            }
        }

        return false;
    }

    private static function satisfiesSingle(string $version, string $constraint): bool
    {
        $constraint = trim($constraint);

        if ($constraint === '*' || $constraint === '') {
            return true;
        }

        if (preg_match('/^([\^~><=!]+)\s*(.+)$/', $constraint, $matches)) {
            $operator = $matches[1];
            $target = ltrim($matches[2], 'v');

            return self::compare($version, $operator, $target);
        }

        if (preg_match('/^(.+)\s*-\s*(.+)$/', $constraint, $matches)) {
            $low = ltrim($matches[1], 'v');
            $high = ltrim($matches[2], 'v');

            return self::compare($version, '>=', $low) && self::compare($version, '<=', $high);
        }

        $parts = explode(',', $constraint);
        $andConstraints = array_map('trim', $parts);

        foreach ($andConstraints as $part) {
            if (!self::satisfiesSingle($version, $part)) {
                return false;
            }
        }

        return true;
    }

    private static function compare(string $version, string $operator, string $target): bool
    {
        $versionParts = explode('.', $version);
        $targetParts = explode('.', $target);

        $vMajor = (int) ($versionParts[0] ?? 0);
        $vMinor = (int) ($versionParts[1] ?? 0);
        $vPatch = (int) ($versionParts[2] ?? 0);

        $tMajor = (int) ($targetParts[0] ?? 0);
        $tMinor = (int) ($targetParts[1] ?? 0);
        $tPatch = (int) ($targetParts[2] ?? 0);

        return match ($operator) {
            '^' => self::satifiesCaret($vMajor, $vMinor, $vPatch, $tMajor, $tMinor, $tPatch),
            '~' => self::satifiesTilde($vMajor, $vMinor, $vPatch, $tMajor, $tMinor, $tPatch),
            '>=' => $vMajor > $tMajor || ($vMajor === $tMajor && ($vMinor > $tMinor || ($vMinor === $tMinor && $vPatch >= $tPatch))),
            '<=' => $vMajor < $tMajor || ($vMajor === $tMajor && ($vMinor < $tMinor || ($vMinor === $tMinor && $vPatch <= $tPatch))),
            '>' => $vMajor > $tMajor || ($vMajor === $tMajor && ($vMinor > $tMinor || ($vMinor === $tMinor && $vPatch > $tPatch))),
            '<' => $vMajor < $tMajor || ($vMajor === $tMajor && ($vMinor < $tMinor || ($vMinor === $tMinor && $vPatch < $tPatch))),
            '!=' => $vMajor !== $tMajor || $vMinor !== $tMinor || $vPatch !== $tPatch,
            '=' => $vMajor === $tMajor && $vMinor === $tMinor && $vPatch === $tPatch,
            default => $vMajor === $tMajor && $vMinor === $tMinor && $vPatch === $tPatch,
        };
    }

    private static function satifiesCaret(
        int $vMajor, int $vMinor, int $vPatch,
        int $tMajor, int $tMinor, int $tPatch,
    ): bool {
        if ($vMajor !== $tMajor) {
            return false;
        }

        if ($tMajor === 0) {
            if ($tMinor === 0) {
                return $vPatch >= $tPatch;
            }

            return $vMinor === $tMinor && $vPatch >= $tPatch;
        }

        return $vMinor >= $tMinor;
    }

    private static function satifiesTilde(
        int $vMajor, int $vMinor, int $vPatch,
        int $tMajor, int $tMinor, int $tPatch,
    ): bool {
        if ($vMajor !== $tMajor) {
            return false;
        }

        if ($vMinor !== $tMinor) {
            return false;
        }

        return $vPatch >= $tPatch;
    }
}
