<?php

declare(strict_types=1);

namespace PrestoWorld\Core\Cache;

use PrestoWorld\Gutenberg\Cache\CacheBridgeInterface;
use PrestoWorld\Gutenberg\Cache\CacheBridge as GutenbergCacheBridge;

/**
 * Bridge app → gutenberg-core (vendor không được biết storage/config của app).
 *
 * Đăng ký 1 lần lúc boot (Builder\Module) → BlockSerializer/TemplatePartHandler
 * đi qua interface này cho enabled/salt/gen/stat. Bridge = null (test/CLI
 * standalone) → caching tự tắt, không đổi hành vi.
 */
final class GutenbergBridge implements CacheBridgeInterface
{
    private static bool $registered = false;

    public static function register(): void
    {
        if (self::$registered) {
            return;
        }

        self::$registered = true;
        GutenbergCacheBridge::set(new self());
    }

    public function enabled(string $ns): bool
    {
        return CacheSettings::enabled($ns);
    }

    public function salt(string $ns): string
    {
        return CacheSalt::current($ns);
    }

    public function generation(string $ns): string
    {
        return CacheGen::current($ns);
    }

    public function stat(string $ns, string $event): void
    {
        StatsCollector::increment($ns . '.' . $event);
    }
}
