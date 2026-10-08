<?php

declare(strict_types=1);

namespace App\Console\Commands;

use PrestoWorld\Core\Compiler\CompileIssue;
use PrestoWorld\Core\Compiler\CompileReport;
use PrestoWorld\Core\Compiler\Mapping\MappingRegistry;
use PrestoWorld\Core\Compiler\SymbolAnalyzer;
use PrestoWorld\Core\Compiler\WpCompiler;
use Witals\Framework\Console\Command;

/**
 * `pw compile <path> [--out=comiled] [--analyze-only] [--target=pgsql] [--map-version=x]`
 * (spec §10.2.2). Compile một plugin/theme WP sang PrestoWorld runtime.
 */
class CompileCommand extends Command
{
    protected string $name = 'compile';
    protected string $description = 'Compile a WordPress plugin/theme to PrestoWorld';

    /** @var array<string, string> */
    protected array $arguments = [
        'path' => 'Path to the plugin/theme directory to compile',
    ];

    /** @var array<string, string> */
    protected array $options = [
        '--out=' => 'Output directory (default: storage/compiled/<slug>)',
        '--analyze-only' => 'Analyze only; do not write output files',
        '--target=' => 'Target dialect: pgsql (default) or mysql',
        '--map-version=' => 'Apply a distributed mapping overlay version',
    ];

    /**
     * @param list<string> $args
     */
    public function handle(array $args): int
    {
        $positionals = [];
        $options = [];
        $valueOptions = ['out', 'target', 'map-version'];
        $i = 0;
        while ($i < count($args)) {
            $arg = $args[$i];
            if (str_starts_with($arg, '--')) {
                $a = substr($arg, 2);
                if (str_contains($a, '=')) {
                    [$key, $value] = explode('=', $a, 2);
                    $options[$key] = $value;
                } elseif (in_array($a, $valueOptions, true) && isset($args[$i + 1])) {
                    $options[$a] = $args[$i + 1];
                    $i++;
                } else {
                    $options[$a] = true;
                }
            } else {
                $positionals[] = $arg;
            }
            $i++;
        }

        $path = $positionals[0] ?? null;
        if ($path === null || $path === '') {
            $this->error('Missing required argument: path');
            $this->displayHelp();

            return 1;
        }

        $path = rtrim($path, '/');
        if (!is_dir($path)) {
            $this->error("Directory not found: {$path}");

            return 1;
        }

        $target = $options['target'] ?? 'pgsql';
        if (!in_array($target, ['pgsql', 'mysql'], true)) {
            $this->error("Invalid --target \"{$target}\" (expected pgsql or mysql)");

            return 1;
        }

        $overlayVersion = $this->stringOption($options, 'map-version', '');
        $mappings = $this->loadMappings($overlayVersion === '' ? null : $overlayVersion);
        if ($mappings === null) {
            return 1;
        }

        $outputDir = $this->resolveOutputDir($path, $this->stringOption($options, 'out', ''));

        $compiler = new WpCompiler($mappings, new SymbolAnalyzer());
        $dryRun = isset($options['analyze-only']);

        $stats = $mappings->stats();
        $this->info(sprintf(
            'Compiling %s → %s (map %s, target %s, %d fn / %d class / %d rules)',
            $path,
            $dryRun ? '(analyze only)' : $outputDir,
            $mappings->version(),
            $target,
            $stats['functions'],
            $stats['classes'],
            $stats['queries'],
        ));

        $report = $compiler->compile($path, $outputDir, $dryRun, 'plugin');
        $this->printReport($report);

        return $report->failedFiles() === [] ? 0 : 1;
    }

    private function loadMappings(?string $overlayVersion): ?MappingRegistry
    {
        try {
            $base = MappingRegistry::fromFile(
                $this->app->basePath() . '/resources/mappings/master.json',
            );

            if ($overlayVersion !== null && $overlayVersion !== '') {
                $overlayPath = $this->app->basePath() . '/resources/mappings/' . $overlayVersion . '.json';
                if (!is_file($overlayPath)) {
                    $this->error("Mapping overlay not found: {$overlayPath}");

                    return null;
                }
                $base->overlay(MappingRegistry::fromFile($overlayPath));
                $this->info("Overlay mapping version \"{$overlayVersion}\" applied.");
            }

            return $base;
        } catch (\Throwable $e) {
            $this->error('Mapping load failed: ' . $e->getMessage());

            return null;
        }
    }

    /**
     * @param array<string, bool|string> $options
     */
    private function stringOption(array $options, string $key, string $default): string
    {
        $value = $options[$key] ?? null;

        return is_string($value) ? $value : $default;
    }

    private function resolveOutputDir(string $path, string $outOption): string
    {
        if ($outOption !== '') {
            return rtrim($outOption, '/');
        }

        $slug = basename($path);

        return $this->app->basePath() . '/storage/compiled/' . $slug;
    }

    private function printReport(CompileReport $report): void
    {
        foreach ($report->failedFiles() as $failure) {
            $this->error("[FAILED] {$failure['file']}: {$failure['error']}");
        }

        foreach ($report->issues() as $issue) {
            $label = match ($issue->type) {
                CompileIssue::UNSUPPORTED_FUNCTION => 'unsupported',
                CompileIssue::UNRESOLVED_CLASS => 'unresolved',
                CompileIssue::QUERY_UNPARSED => 'query',
                CompileIssue::UNSAFE_SQL => 'unsafe-sql',
                CompileIssue::USER_FN_SKIPPED => 'skipped-fn',
                CompileIssue::STRUCTURAL_CLASS => 'structural',
                CompileIssue::PLUGGABLE_FUNCTION => 'pluggable',
                default => 'info',
            };
            $this->warn(sprintf('  [%s] %s (line %d): %s', $label, $issue->symbol, $issue->line, $issue->reason ?? ''));
        }

        $stats = $report->stats();
        $summary = sprintf(
            'Completed: %d files | %d SQL rewritten | %d WP functions mapped | %d classes mapped | %d user fns compiled | %d skipped | %d leftover',
            $stats['files_scanned'] ?? 0,
            $stats['queries_transformed'] ?? 0,
            $stats['wp_functions_mapped'] ?? 0,
            $stats['classes_mapped'] ?? 0,
            $stats['user_functions_compiled'] ?? 0,
            $stats['user_functions_skipped'] ?? 0,
            $stats['user_functions_leftover'] ?? 0,
        );

        $this->info($summary);
        $this->info('Score: ' . $report->score() . '/100');

        if ($report->isAnalyzeOnly()) {
            $this->comment('Analyze only — nothing was written.');
        } else {
            $this->comment('Manifest: ' . $report->plugin() . '/manifest.json');
        }
    }
}