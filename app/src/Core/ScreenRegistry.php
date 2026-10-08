<?php

declare(strict_types=1);

namespace PrestoWorld\Core;

/**
 * ScreenRegistry — get_current_screen/add_screen_option... (spec 10 §10.4 Group admin).
 */
final class ScreenRegistry
{
    /** @var array<string, mixed> */
    private static array $current = [];

    /** @var array<string, array{enabled: bool}> */
    private static array $options = [];

    private function __construct()
    {
    }

    public static function current(): ?ScreenRegistry
    {
        if (self::$current === []) {
            return null;
        }

        return new self();
    }

    public static function set(array $screen): void
    {
        self::$current = $screen;
    }

    public static function addOption(string $option, mixed $args = []): void
    {
        self::$options[$option] = ['enabled' => true];
    }

    public static function removeOption(string $option): void
    {
        unset(self::$options[$option]);
    }

    public static function hasOption(string $option): bool
    {
        return isset(self::$options[$option]);
    }

    public function id(): string
    {
        return (string) (self::$current['id'] ?? '');
    }

    public function base(): string
    {
        return (string) (self::$current['base'] ?? '');
    }

    public function action(): string
    {
        return (string) (self::$current['action'] ?? '');
    }

    public function postType(): string
    {
        return (string) (self::$current['post_type'] ?? '');
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return self::$current[$key] ?? $default;
    }

    public static function reset(): void
    {
        self::$current = [];
        self::$options = [];
    }
}