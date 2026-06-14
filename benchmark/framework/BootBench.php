<?php

declare(strict_types=1);

namespace Benchmark\Framework;

use App\Foundation\Application;
use Witals\Framework\Contracts\RuntimeType;

class BootBench extends Benchmark
{
    private ?Application $app = null;

    public function run(): array
    {
        $results = [];

        // 1. Full boot (constructor + boot)
        $results['full_boot'] = $this->measure(function () {
            $app = new Application(getcwd(), RuntimeType::TRADITIONAL);
            $app->boot();
        }, 20);

        // 2. Constructor only
        $results['constructor'] = $this->measure(function () {
            $app = new Application(getcwd(), RuntimeType::TRADITIONAL);
        }, 20);

        // 3. Boot only (warm app)
        $this->ensureWarmApp();
        $results['boot_only'] = $this->measure(function () {
            $app = new Application(getcwd(), RuntimeType::TRADITIONAL);
            $app->boot();
        }, 20);

        // 4. Module discovery (Witals ModuleManager)
        $results['module_discovery'] = $this->measure(function () {
            $manager = $this->app->make(\Witals\Framework\Module\ModuleManager::class);
            $ref = new \ReflectionClass($manager);
            $discoverMethod = $ref->getMethod('discover');
            $discoverMethod->setAccessible(true);
            $discoverMethod->invoke($manager);
        }, 10);

        // 5. Memory: boot footprint
        $memBefore = memory_get_usage(true);
        $warm = new Application(getcwd(), RuntimeType::TRADITIONAL);
        $warm->boot();
        $memAfter = memory_get_usage(true);
        $results['memory_footprint'] = [
            'value' => round(($memAfter - $memBefore) / 1024 / 1024, 2) . ' MB',
        ];

        return $results;
    }

    private function ensureWarmApp(): void
    {
        if ($this->app === null) {
            $this->app = new Application(getcwd(), RuntimeType::TRADITIONAL);
            $this->app->boot();
        }
    }
}
