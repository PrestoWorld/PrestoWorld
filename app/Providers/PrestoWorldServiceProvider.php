<?php

declare(strict_types=1);

namespace App\Providers;

use App\Support\ServiceProvider;
use PrestoWorld\Core\Database\PrestoWpdb;
use PrestoWorld\Core\Legacy\LegacyHook;
use PrestoWorld\Core\Legacy\LegacyRegistry;
use PrestoWorld\Core\Legacy\LegacyState;
use PrestoWorld\Core\Legacy\LegacyStateResetter;
use Prestoworld\WordpressShim\ShimLoader;
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

        // Lifecycle adapter: quét ResettableInterface gọi LegacyState::reset()
        // sau mỗi request (spec 10 §10.7.2).
        $this->singleton(LegacyStateResetter::class);
        $this->app->instance(LegacyStateResetter::class, new LegacyStateResetter());
    }

    public function boot(): void
    {
        if (function_exists('storage_path')) {
            $shims = new ShimLoader();
            $shims->loadGroup('hooks');
            $shims->loadGroup('compat');
            $shims->registerAutoload();
        }

        $this->bootContextBuilder();

        LegacyState::initGlobals();
        LegacyState::registry();

        $db = null;
        try {
            $resolved = $this->app->make(\Cycle\Database\DatabaseInterface::class);
            if ($resolved instanceof \Cycle\Database\DatabaseInterface) {
                $db = $resolved;
            }
        } catch (\Throwable) {
            $db = null;
        }

        if ($db !== null) {
            OptionRepository::setDatabase($db);
        }
    }

    /**
     * Bootstrap the Context Builder (framework/presto/Context/Builder):
     * binds Presto\Context\Loader (backed by the gutenberg-core kernel),
     * boots the active theme's functions.php and registers REST routes.
     *
     * The Builder lives outside modules/ so it is not part of module
     * discovery — it is wired explicitly here.
     */
    private function bootContextBuilder(): void
    {
        try {
            if (!class_exists(\PrestoWorld\Modules\ContextBuilder\Module::class)) {
                return;
            }

            $path = $this->app->basePath('framework/presto/Context/Builder');

            $module = new \PrestoWorld\Modules\ContextBuilder\Module(
                $this->app,
                $path,
                ['name' => 'context-builder'],
            );

            $module->register();
            $module->boot();
        } catch (\Throwable $e) {
            error_log('[PrestoWorld] Context Builder boot failed: ' . $e->getMessage());
        }
    }
}