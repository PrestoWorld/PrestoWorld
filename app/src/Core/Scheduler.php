<?php

declare(strict_types=1);

namespace PrestoWorld\Core;

/**
 * Scheduler — WP-Cron → Spiral Queue (spec 10 §10.4.18 Group 17).
 *
 * Bản in-memory đủ cho shim/compile-time rewrite; backend queue thật được
 * wiring ở layer sau. Mọi method static để khớp StaticCall do compiler sinh.
 */
final class Scheduler
{
    /**
     * @var list<array{timestamp: int, hook: string, args: array<int, mixed>, schedule: string|null, interval: int|null}>
     */
    private static array $events = [];

    private function __construct()
    {
    }

    /**
     * @param array<int, mixed> $args
     */
    public static function once(int $timestamp, string $hook, array $args = []): bool
    {
        self::add($timestamp, $hook, $args, null, null);

        return true;
    }

    /**
     * @param array<int, mixed> $args
     */
    public static function recurring(int $timestamp, string $recurrence, string $hook, array $args = []): bool
    {
        $schedules = self::schedules();
        if (!isset($schedules[$recurrence])) {
            return false;
        }

        self::add($timestamp, $hook, $args, $recurrence, $schedules[$recurrence]['interval']);

        return true;
    }

    /**
     * @param array<int, mixed>|null $args null → clear mọi event của hook
     */
    public static function clear(string $hook, ?array $args = null): int
    {
        $before = count(self::$events);
        self::$events = array_values(array_filter(
            self::$events,
            static function (array $event) use ($hook, $args): bool {
                if ($event['hook'] !== $hook) {
                    return true;
                }

                return $args !== null && $event['args'] !== $args;
            },
        ));

        return $before - count(self::$events);
    }

    /**
     * @param array<int, mixed> $args
     */
    public static function unschedule(int $timestamp, string $hook, array $args = []): bool
    {
        $found = false;
        self::$events = array_values(array_filter(
            self::$events,
            static function (array $event) use ($timestamp, $hook, $args, &$found): bool {
                if ($event['timestamp'] === $timestamp && $event['hook'] === $hook && $event['args'] === $args) {
                    $found = true;

                    return false;
                }

                return true;
            },
        ));

        return $found;
    }

    /**
     * @param array<int, mixed> $args
     */
    public static function next(string $hook, array $args = []): int|false
    {
        $timestamps = [];
        foreach (self::$events as $event) {
            if ($event['hook'] === $hook && $event['args'] === $args) {
                $timestamps[] = $event['timestamp'];
            }
        }

        return $timestamps === [] ? false : min($timestamps);
    }

    /**
     * @param array<int, mixed> $args
     * @return array{timestamp: int, schedule: string|null, args: array<int, mixed>}|false
     */
    public static function event(int $timestamp, string $hook, array $args = []): array|false
    {
        foreach (self::$events as $event) {
            if ($event['timestamp'] === $timestamp && $event['hook'] === $hook && $event['args'] === $args) {
                return [
                    'timestamp' => $event['timestamp'],
                    'schedule' => $event['schedule'],
                    'args' => $event['args'],
                ];
            }
        }

        return false;
    }

    /**
     * @return array<string, array{interval: int, display: string}>
     */
    public static function schedules(): array
    {
        return [
            'hourly' => ['interval' => 3600, 'display' => 'Once Hourly'],
            'twicedaily' => ['interval' => 43200, 'display' => 'Twice Daily'],
            'daily' => ['interval' => 86400, 'display' => 'Once Daily'],
            'weekly' => ['interval' => 604800, 'display' => 'Once Weekly'],
        ];
    }

    public static function run(bool $force = false): void
    {
        $now = time();
        $remaining = [];
        foreach (self::$events as $event) {
            if ($event['timestamp'] > $now) {
                $remaining[] = $event;
                continue;
            }

            self::invoke($event['hook'], $event['args']);

            if ($event['schedule'] !== null && $event['interval'] !== null) {
                $event['timestamp'] = $now + $event['interval'];
                $remaining[] = $event;
            }
        }

        self::$events = $remaining;
    }

    public static function spawn(): bool
    {
        self::run();

        return true;
    }

    public static function reset(): void
    {
        self::$events = [];
    }

    /**
     * @param array<int, mixed> $args
     */
    private static function add(int $timestamp, string $hook, array $args, ?string $schedule, ?int $interval): void
    {
        self::$events[] = [
            'timestamp' => $timestamp,
            'hook' => $hook,
            'args' => $args,
            'schedule' => $schedule,
            'interval' => $interval,
        ];
    }

    /**
     * @param array<int, mixed> $args
     */
    private static function invoke(string $hook, array $args): void
    {
        if (!function_exists('do_action')) {
            return;
        }

        try {
            do_action($hook, ...$args);
        } catch (\Throwable) {
            // Cron callback lỗi không được làm hỏng toàn bộ lần chạy.
        }
    }
}
