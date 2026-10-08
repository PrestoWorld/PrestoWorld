<?php

declare(strict_types=1);

namespace PrestoWorld\Core\Support;

use Witals\Framework\Container\Container;

/**
 * Bridge từ PrestoWorld\Core sang container ứng dụng (Witals).
 *
 * Mobile: mọi service Core hoạt động được kể cả khi không có container
 * (vd. PHPUnit standalone / CLI), dùng default thay vì fatal.
 */
final class App
{
    private static ?Container $container = null;

    private function __construct()
    {
    }

    public static function container(): ?Container
    {
        if (self::$container !== null) {
            return self::$container;
        }

        return Container::getInstance();
    }

    public static function setContainer(?Container $container): void
    {
        self::$container = $container;
    }

    /**
     * Resolve service từ container; trả về $default nếu không có container
     * hoặc abstract chưa được bind.
     *
     * @template T of object
     * @param class-string<T> $abstract
     * @param T $default
     * @return T
     */
    public static function make(string $abstract, mixed $default): mixed
    {
        $container = self::container();
        if ($container === null || !$container->has($abstract)) {
            return $default;
        }

        try {
            return $container->make($abstract);
        } catch (\Throwable) {
            return $default;
        }
    }

    }