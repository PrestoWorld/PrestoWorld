<?php

declare(strict_types=1);

namespace PrestoWorld\Core;

/**
 * MetaBoxRegistry — add_meta_box/remove_meta_box/render_meta_box (spec 10 §10.4 Group admin).
 */
final class MetaBoxRegistry
{
    /** @var array<string, array{id: string, title: string, callback: callable, context: string, priority: string}> */
    private static array $boxes = [];

    /** @var array<string, array<string, mixed>> */
    private static array $saved = [];

    private function __construct()
    {
    }

    public static function add(
        string $id,
        string $title,
        callable $callback,
        string $screen = '',
        string $context = 'normal',
        string $priority = 'default',
    ): void {
        self::$boxes[$id] = [
            'id' => $id,
            'title' => $title,
            'callback' => $callback,
            'context' => $context,
            'priority' => $priority,
        ];
    }

    public static function remove(string $id): bool
    {
        $had = isset(self::$boxes[$id]);
        unset(self::$boxes[$id]);

        return $had;
    }

    public static function render(string $id, mixed ...$args): string
    {
        if (!isset(self::$boxes[$id])) {
            return '';
        }

        try {
            $result = call_user_func(self::$boxes[$id]['callback'], ...$args);
        } catch (\Throwable) {
            return '';
        }

        if ($result === null || is_scalar($result)) {
            return (string) $result;
        }

        if (is_array($result)) {
            $out = '';
            foreach ($result as $item) {
                if (is_scalar($item)) {
                    $out .= (string) $item;
                }
            }

            return $out;
        }

        return '';
    }

    public static function save(string $id, mixed ...$args): void
    {
        self::$saved[$id] = ['args' => $args, 'time' => time()];
    }

    /** @return list<string> */
    public static function ids(): array
    {
        return array_keys(self::$boxes);
    }

    public static function reset(): void
    {
        self::$boxes = [];
        self::$saved = [];
    }
}