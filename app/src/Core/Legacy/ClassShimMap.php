<?php

declare(strict_types=1);

namespace PrestoWorld\Core\Legacy;

/**
 * ClassShimMap (spec 10 §10.5 mode 's') — map tên class WP_* → class PrestoWorld.
 *
 * Chỉ liệt kê target đã tồn tại trong Core; resolve() trả file shim (alias)
 * hoặc null để autoloader khác xử lý.
 */
final class ClassShimMap
{
    /** @var array<string, class-string> */
    private const MAP = [
        'WP_Query' => \PrestoWorld\Core\Post\PostQuery::class,
        'WP_Post' => \PrestoWorld\Core\Post\PostEntity::class,
        'WP_User' => \PrestoWorld\Core\User\UserEntity::class,
        'WP_Error' => \PrestoWorld\Core\Error\PrestoError::class,
    ];

    private function __construct()
    {
    }

    /**
     * Trả về file shim chứa alias cho $class, null nếu không có trong map.
     */
    public static function resolve(string $class): ?string
    {
        if (!isset(self::MAP[$class])) {
            return null;
        }

        return __DIR__ . '/wp-class-shims.php';
    }

    /**
     * @return array<string, class-string>
     */
    public static function targets(): array
    {
        return self::MAP;
    }
}