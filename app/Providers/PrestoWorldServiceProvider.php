<?php

declare(strict_types=1);

namespace App\Providers;

use App\Support\ServiceProvider;
use PrestoWorld\Core\Database\PrestoWpdb;
use PrestoWorld\Core\Legacy\LegacyHook;
use PrestoWorld\Core\Legacy\LegacyRegistry;
use PrestoWorld\Core\Legacy\LegacyState;
use PrestoWorld\Core\Legacy\ShimLoader;
use PrestoWorld\Core\OptionRepository;
use Witals\Framework\Module\Contracts\HookInterface;

/**
 * WS-B Runtime Shim Layer (spec 10 §10.7 + 06 Legacy Support):
 * - Bind HookInterface → LegacyHook (override ModuleServiceProvider,
 *   đăng ký sau core providers nên instance() thắng HookDispatcher binding).
 * - Nạp shim groups + autoload class shim.
 * - Gắn DB cho OptionRepository/LegacyState.
 */
class PrestoWorldServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(HookInterface::class, LegacyHook::class);
        $this->app->instance(HookInterface::class, new LegacyHook());

        $this->singleton(LegacyRegistry::class, function (): LegacyRegistry {
            $registry = new LegacyRegistry();
            $ledger = storage_path('framework/legacy-registry.sqlite');
            if (is_dir(dirname($ledger))) {
                try {
                    $registry->attachLedger($ledger);
                } catch (\Throwable) {
                    // Ledger optional — registry in-memory vẫn chạy.
                }
            }

            return $registry;
        });

        $this->singleton(PrestoWpdb::class, function (): PrestoWpdb {
            return PrestoWpdb::instance();
        });
    }

    public function boot(): void
    {
        if (function_exists('storage_path')) {
            $shims = new ShimLoader();
            $shims->loadGroup('hooks');
            $shims->loadGroup('compat');
            $shims->registerAutoload();
        }

        $db = null;
        try {
            $db = $this->app->make(\Cycle\Database\DatabaseInterface::class);
        } catch (\Throwable) {
            $db = null;
        }

        if ($db !== null) {
            OptionRepository::setDatabase($db);
        }

        LegacyState::registry();
    }
}