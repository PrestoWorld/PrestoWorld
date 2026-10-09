<?php

declare(strict_types=1);

namespace PrestoWorld\Core\Widget;

/**
 * WidgetFactory — replaces WP_Widget_Factory (spec 10 §10.5).
 */
final class WidgetFactory
{
    /** @var array<string, mixed> */
    private static array $widgets = [];

    private function __construct()
    {
    }

    public static function register(mixed $widget): void
    {
        $id = self::resolveId($widget);
        if ($id === '') {
            return;
        }

        self::$widgets[$id] = $widget;
    }

    public static function unregister(string $widgetId): bool
    {
        $had = isset(self::$widgets[$widgetId]);
        unset(self::$widgets[$widgetId]);

        return $had;
    }

    public static function isRegistered(string $id): bool
    {
        return isset(self::$widgets[$id]);
    }

    public static function get(string $id): mixed
    {
        return self::$widgets[$id] ?? null;
    }

    /**
     * @return array<string, mixed>
     */
    public static function all(): array
    {
        return self::$widgets;
    }

    public static function reset(): void
    {
        self::$widgets = [];
    }

    private static function resolveId(mixed $widget): string
    {
        if (is_string($widget)) {
            return $widget;
        }

        if (is_object($widget)) {
            if (method_exists($widget, 'idBase')) {
                $id = $widget->idBase();
                if (is_string($id) && $id !== '') {
                    return $id;
                }
            }

            if (property_exists($widget, 'id_base')) {
                $id = $widget->id_base;
                if (is_string($id) && $id !== '') {
                    return $id;
                }
            }

            return $widget::class;
        }

        return '';
    }
}
