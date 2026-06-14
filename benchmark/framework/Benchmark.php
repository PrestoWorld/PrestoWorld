<?php

declare(strict_types=1);

namespace Benchmark\Framework;

abstract class Benchmark
{
    protected string $name;

    public function __construct()
    {
        $this->name = (new \ReflectionClass($this))->getShortName();
    }

    abstract public function run(): array;

    protected function measure(callable $fn, int $iterations = 1000): array
    {
        // Warm-up
        for ($i = 0; $i < 10; $i++) {
            $fn();
        }

        $start = hrtime(true);
        for ($i = 0; $i < $iterations; $i++) {
            $fn();
        }
        $end = hrtime(true);

        $totalNs = $end - $start;
        $avgNs = $totalNs / $iterations;

        return [
            'iterations' => $iterations,
            'total_ms' => round($totalNs / 1_000_000, 2),
            'avg_us' => round($avgNs / 1_000, 2),
            'avg_ns' => round($avgNs, 0),
            'ops_per_sec' => $iterations > 0
                ? number_format(round($iterations / ($totalNs / 1_000_000_000)))
                : 'N/A',
        ];
    }

    protected function measureMemory(callable $fn): array
    {
        $before = memory_get_usage(true);
        $fn();
        $after = memory_get_usage(true);

        return [
            'memory_delta_bytes' => $after - $before,
            'memory_delta_kb' => round(($after - $before) / 1024, 2),
        ];
    }
}
