<?php

declare(strict_types=1);

namespace PrestoWorld\Core\Compiler;

/**
 * Compatibility Report (§10.9.1) + scoring (§10.9.2).
 */
final class CompileReport
{
    public const STAT_FILES_SCANNED = 'files_scanned';
    public const STAT_QUERIES_TRANSFORMED = 'queries_transformed';
    public const STAT_WP_FUNCTIONS_MAPPED = 'wp_functions_mapped';
    public const STAT_CLASSES_MAPPED = 'classes_mapped';
    public const STAT_USER_FUNCTIONS_COMPILED = 'user_functions_compiled';
    public const STAT_USER_FUNCTIONS_SKIPPED = 'user_functions_skipped';
    public const STAT_USER_FUNCTIONS_LEFTOVER = 'user_functions_leftover';

    /** @var array<string, int> */
    private array $stats;

    /** @var list<CompileIssue> */
    private array $issues = [];

    /** @var array<string, string> user fn → FQCN::method */
    private array $userFunctionMap = [];

    /** @var list<array{file: string, error: string}> */
    private array $failedFiles = [];

    private string $compiledAt;

    /**
     * @param array<string, int> $stats
     */
    public function __construct(
        private readonly string $plugin,
        private readonly string $mappingVersion,
        private readonly bool $analyzeOnly = false,
        array $stats = [],
    ) {
        $this->stats = $stats + [
            self::STAT_FILES_SCANNED => 0,
            self::STAT_QUERIES_TRANSFORMED => 0,
            self::STAT_WP_FUNCTIONS_MAPPED => 0,
            self::STAT_CLASSES_MAPPED => 0,
            self::STAT_USER_FUNCTIONS_COMPILED => 0,
            self::STAT_USER_FUNCTIONS_SKIPPED => 0,
            self::STAT_USER_FUNCTIONS_LEFTOVER => 0,
        ];
        $this->compiledAt = gmdate('Y-m-d\TH:i:s\Z');
    }

    public function plugin(): string
    {
        return $this->plugin;
    }

    public function mappingVersion(): string
    {
        return $this->mappingVersion;
    }

    public function isAnalyzeOnly(): bool
    {
        return $this->analyzeOnly;
    }

    public function incrementStat(string $key, int $by = 1): void
    {
        $this->stats[$key] = ($this->stats[$key] ?? 0) + $by;
    }

    public function stat(string $key): int
    {
        return $this->stats[$key] ?? 0;
    }

    /**
     * @return array<string, int>
     */
    public function stats(): array
    {
        return $this->stats;
    }

    public function addIssue(CompileIssue $issue): void
    {
        $this->issues[] = $issue;
    }

    public function addFailure(string $file, string $error): void
    {
        $this->failedFiles[] = ['file' => $file, 'error' => $error];
    }

    /**
     * @return list<array{file: string, error: string}>
     */
    public function failedFiles(): array
    {
        return $this->failedFiles;
    }

    /**
     * @return list<CompileIssue>
     */
    public function issues(): array
    {
        return $this->issues;
    }

    public function mapUserFunction(string $functionName, string $target): void
    {
        $this->userFunctionMap[$functionName] = $target;
    }

    /**
     * @return array<string, string>
     */
    public function userFunctionMap(): array
    {
        return $this->userFunctionMap;
    }

    /**
     * Bảng trừ điểm §10.9.2 — điểm thấp nhất là 0.
     */
    public function score(): int
    {
        $score = 100;

        $unsupportedGroups = [];
        foreach ($this->issues as $issue) {
            if ($issue->type === CompileIssue::UNSUPPORTED_FUNCTION) {
                $unsupportedGroups[$issue->group ?? $issue->symbol] = true;
            }
        }
        $score -= 20 * count($unsupportedGroups);

        foreach ($this->issues as $issue) {
            if ($issue->type === CompileIssue::QUERY_UNPARSED) {
                $score -= 30;
            }
            if ($issue->type === CompileIssue::STRUCTURAL_CLASS) {
                $score -= 10;
            }
        }

        if ($this->analyzeOnly) {
            $score -= 5;
        }

        $score -= 3 * $this->stat(self::STAT_USER_FUNCTIONS_LEFTOVER);
        $score -= 1 * $this->stat(self::STAT_USER_FUNCTIONS_SKIPPED);

        return max(0, $score);
    }

    /**
     * Report format §10.9.1 + manifest fields §10.2.2.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $fallbackShims = [];
        $unresolved = [];
        foreach ($this->issues as $issue) {
            if ($issue->type === CompileIssue::UNSUPPORTED_FUNCTION) {
                $fallbackShims[] = $issue->symbol;
            }
            if ($issue->type === CompileIssue::UNRESOLVED_CLASS) {
                $unresolved[] = $issue->symbol . ($issue->reason !== null ? " ({$issue->reason})" : '');
            }
        }

        return [
            'plugin' => $this->plugin,
            'compiled_at' => $this->compiledAt,
            'mapping_version' => $this->mappingVersion,
            'compatibility_score' => $this->score(),
            'stats' => $this->stats,
            'user_function_map' => $this->userFunctionMap,
            'issues' => array_map(
                static fn (CompileIssue $issue): array => $issue->toArray(),
                $this->issues,
            ),
            'failed_files' => $this->failedFiles,
            'fallback_shims' => array_values(array_unique($fallbackShims)),
            'unresolved' => array_values(array_unique($unresolved)),
        ];
    }

    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '{}';
    }
}
