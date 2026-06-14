<?php

declare(strict_types=1);

/**
 * PrestoWorld + Witals Framework Benchmark Suite
 *
 * Usage: php benchmark/run.php [--filter=BenchName]
 */

// ── Bootstrap ──────────────────────────────────────────────────────

$rootDir = dirname(__DIR__);
require $rootDir . '/vendor/autoload.php';

// ── Autoload benchmarks ────────────────────────────────────────────

spl_autoload_register(function (string $class) use ($rootDir) {
    $prefix = 'Benchmark\\';
    if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
        return;
    }
    $relative = substr($class, strlen($prefix));
    $file = $rootDir . '/benchmark/' . str_replace('\\', '/', $relative) . '.php';
    if (file_exists($file)) {
        require $file;
    }
});

// ── Helpers ────────────────────────────────────────────────────────

function color(string $text, string $color): string
{
    $colors = [
        'green' => "\033[32m",
        'yellow' => "\033[33m",
        'blue' => "\033[34m",
        'magenta' => "\033[35m",
        'cyan' => "\033[36m",
        'red' => "\033[31m",
        'bold' => "\033[1m",
        'reset' => "\033[0m",
    ];
    return $colors[$color] . $text . $colors['reset'];
}

function formatResult(string $key, array|string $result): string
{
    if (is_string($result)) {
        return $result;
    }
    if (isset($result['avg_us'])) {
        return sprintf(
            '%s  │  %s  │  %s/s  │  %s',
            color(str_pad(number_format($result['avg_us'], 2) . ' µs', 14, ' ', STR_PAD_LEFT), 'cyan'),
            color(str_pad(number_format($result['total_ms'], 2) . ' ms', 12, ' ', STR_PAD_LEFT), 'yellow'),
            color(str_pad($result['ops_per_sec'], 14, ' ', STR_PAD_LEFT), 'green'),
            color($result['iterations'] . ' iters', 'magenta')
        );
    }
    if (isset($result['memory_delta_kb'])) {
        return sprintf(
            '%s  │  peak: %s',
            color(str_pad(number_format($result['memory_delta_kb'], 2) . ' KB', 14, ' ', STR_PAD_LEFT), 'cyan'),
            color($result['memory_delta_kb'] . ' KB', 'yellow')
        );
    }
    if (isset($result['value'])) {
        return color($result['value'], 'cyan');
    }
    return json_encode($result);
}

function printHeader(string $title): void
{
    $pad = 76;
    echo "\n" . color(' ╭' . str_repeat('─', $pad) . '╮', 'bold');
    echo "\n" . color(' │', 'bold')
        . str_pad(' ' . $title, $pad + 1)
        . color('│', 'bold');
    echo "\n" . color(' ╰' . str_repeat('─', $pad) . '╯', 'bold');
    echo "\n";
    echo color('  Metric' . str_repeat(' ', 30) . 'Avg', 'bold')
        . color('     Total      ', 'bold')
        . color('  Throughput   ', 'bold')
        . color('  Iterations', 'bold') . "\n";
    echo color('  ' . str_repeat('─', 74), 'bold') . "\n";
}

// ── Discover benchmarks ────────────────────────────────────────────

$filter = null;
if ($argc > 1 && str_starts_with($argv[1], '--filter=')) {
    $filter = substr($argv[1], 9);
}

$benchmarks = [];
$dir = new RecursiveDirectoryIterator(__DIR__);
$iterator = new RecursiveIteratorIterator($dir);
foreach ($iterator as $file) {
    if ($file->isFile() && $file->getExtension() === 'php' && $file->getBasename() !== 'Benchmark.php' && $file->getBasename() !== 'run.php') {
        $relative = substr($file->getPathname(), strlen(__DIR__) + 1);
        $class = 'Benchmark\\' . str_replace(['/', '.php'], ['\\', ''], $relative);
        if ($filter !== null && !str_contains($class, $filter)) {
            continue;
        }
        if (class_exists($class)) {
            $ref = new ReflectionClass($class);
            if (!$ref->isAbstract() && $ref->isSubclassOf(\Benchmark\Framework\Benchmark::class)) {
                $benchmarks[] = $class;
            }
        }
    }
}

if ($benchmarks === []) {
    echo color("No benchmarks found", 'red') . "\n";
    if ($filter !== null) {
        echo "Filter: {$filter}\n";
    }
    exit(1);
}

// ── Run benchmarks ─────────────────────────────────────────────────

echo color("\n 🏁  PrestoWorld + Witals Framework Benchmark Suite\n", 'bold');
echo color(' ⚡  ' . date('Y-m-d H:i:s') . "  |  PHP " . PHP_VERSION . "  |  " . php_uname('m') . "\n\n", 'blue');

$allResults = [];

foreach ($benchmarks as $class) {
    $shortName = (new ReflectionClass($class))->getShortName();
    $group = str_contains($class, 'PrestoWorld') ? 'PrestoWorld' : 'Witals Framework';

    printHeader(" {$group} :: {$shortName} ");

    $instance = new $class();
    $results = $instance->run();
    $allResults[$class] = $results;

    foreach ($results as $key => $result) {
        $label = str_pad('  ' . str_replace('_', ' ', $key), 34, ' ', STR_PAD_RIGHT);
        echo color($label, 'bold');
        echo formatResult($key, $result) . "\n";
    }
}

// ── Summary ────────────────────────────────────────────────────────

echo "\n" . color(' ╭' . str_repeat('─', 76) . '╮', 'bold');
echo "\n" . color(' │', 'bold') . color('  SUMMARY', 'bold') . "\n";
echo color(' ╰' . str_repeat('─', 76) . '╯', 'bold') . "\n";

echo color('  PHP Version:   ', 'bold') . PHP_VERSION . "\n";
echo color('  Platform:      ', 'bold') . php_uname('s') . ' ' . php_uname('r') . ' ' . php_uname('m') . "\n";
echo color('  Memory limit:  ', 'bold') . ini_get('memory_limit') . "\n";
echo color('  Opcache:       ', 'bold') . (function_exists('opcache_get_status') && opcache_get_status(false) ? 'enabled' : 'disabled') . "\n";
echo color('  JIT:           ', 'bold') . (function_exists('opcache_get_status') && ($status = opcache_get_status(false)) && ($status['jit']['en'] ?? false) ? 'enabled' : 'disabled') . "\n";
echo color('  Benchmarks run:', 'bold') . ' ' . count($benchmarks) . "\n\n";

// ── System info ────────────────────────────────────────────────────

$load = sys_getloadavg();
echo color('  System Load (1/5/15 min): ', 'bold')
    . round($load[0], 2) . ' / ' . round($load[1], 2) . ' / ' . round($load[2], 2) . "\n";
echo color('  Memory used by runner:   ', 'bold')
    . round(memory_get_peak_usage(true) / 1024 / 1024, 2) . " MB\n\n";
