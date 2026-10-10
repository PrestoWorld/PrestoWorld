<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\Gutenberg\Theme;

use PrestoWorld\Core\Config;
use PrestoWorld\Core\Legacy\ShimLoader;
use PrestoWorld\Modules\ContextBuilder\BlockRegistry;

/**
 * ThemeBooter — nạp theme theo vòng đời WordPress: boot functions.php tại
 * bước "setup theme".
 *
 * PrestoWorld KHÔNG chấp nhận "user functions" (kể cả functions của WordPress)
 * chạy trực tiếp trong code theme. Mọi hàm WP mà theme dùng phải được khai báo
 * trong WordPress compile layer (Core Legacy: wp-compatibility/wp-shims + class
 * shims) — các shim này dịch tên WP sang cấu trúc PrestoWorld trước khi
 * functions.php được đọc.
 *
 * Chủ đề tự quyết định cách load theo runtime (design pattern):
 *  - Khi chạy trong WordPress            → functions.php load như code hiện tại.
 *  - Khi chạy trong PrestoWorld (PRESTOWORLD)
 *      → functions.php rẽ nhánh sang loader riêng của theme (presto/bootstrap.php),
 *        loader đó chỉ dùng API PrestoWorld (BlockFactory/BlockRegistry), không
 *        gọi raw WP function.
 */
final class ThemeBooter
{
    /**
     * Định danh runtime PrestoWorld — theme rẽ nhánh trên hằng số này.
     */
    public const PRESTOWORLD = 'PRESTOWORLD';

    private static ?string $themePath = null;

    private static bool $booted = false;

    private static ?BlockRegistry $blockRegistry = null;

    /** @var list<\Closure(): void> */
    private static array $deferred = [];

    private function __construct()
    {
    }

    /**
     * Boot functions.php của theme (chạy đúng một lần), đồng thời kích hoạt
     * compile layer để các hàm WP mà theme dùng được dịch sang PrestoWorld.
     */
    public static function boot(string $themePath): void
    {
        // Custom frontend mode: never load the theme engine.
        if (\App\Services\Frontend\FrontendMode::isCustom()) {
            return;
        }

        $themePath = rtrim($themePath, '/');

        if (self::$booted) {
            return;
        }

        if (!is_dir($themePath) || !is_file($themePath . '/functions.php')) {
            return;
        }

        self::$booted = true;
        self::$themePath = $themePath;

        if (!defined('ABSPATH')) {
            define('ABSPATH', dirname($themePath) . '/');
        }

        if (!defined(self::PRESTOWORLD)) {
            define(self::PRESTOWORLD, true);
        }

        self::prepareGlobalState($themePath);
        self::loadCompileLayer();

        require_once $themePath . '/functions.php';

        if (function_exists('do_action')) {
            do_action('after_setup_theme');
        }
    }

    /**
     * Trỏ bộ legacy (get_template_directory/get_template/get_stylesheet...) vào
     * theme đang boot, giống Module ClassicTheme làm cho classic theme.
     */
    private static function prepareGlobalState(string $themePath): void
    {
        if (!class_exists(Config::class)) {
            return;
        }

        $name = basename($themePath);

        Config::set('theme_dir', dirname($themePath));
        Config::set('template', $name);
        Config::set('stylesheet', $name);
    }

    /**
     * Bảo đảm WordPress compile layer đã nạp trước khi chạy functions.php:
     * hooks (add_action/do_action/...) + compat (get_template_directory/...)
     * + class shims (WP_* → PrestoWorld).
     */
    private static function loadCompileLayer(): void
    {
        if (!class_exists(ShimLoader::class)) {
            return;
        }

        $shims = new ShimLoader();
        $shims->loadGroup('hooks');
        $shims->loadGroup('compat');
        $shims->registerAutoload();
    }

    public static function provideBlockRegistry(BlockRegistry $registry): void
    {
        self::$blockRegistry = $registry;
        self::flushDeferred();
    }

    public static function blockRegistry(): ?BlockRegistry
    {
        return self::$blockRegistry;
    }

    /**
     * Đăng ký lại việc cần làm khi registry sẵn sàng — theme boot không phụ
     * thuộc thứ tự boot của module (Gutenberg có thể boot trước ContextBuilder).
     */
    public static function defer(\Closure $callback): void
    {
        self::$deferred[] = $callback;
        self::flushDeferred();
    }

    private static function flushDeferred(): void
    {
        if (self::$blockRegistry === null) {
            return;
        }

        $queue = self::$deferred;
        self::$deferred = [];

        foreach ($queue as $callback) {
            $callback();
        }
    }

    public static function themePath(): ?string
    {
        return self::$themePath;
    }

    public static function booted(): bool
    {
        return self::$booted;
    }
}