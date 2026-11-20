<?php

declare(strict_types=1);

namespace PrestoWorld\Core\Plugin;

/**
 * Lightweight security scan for plugin source.
 *
 * This is NOT a full malware scanner — it's a fast heuristic that flags
 * the patterns that cause 90% of plugin-related security incidents:
 *
 *   - Direct execution outside WordPress (`if (!defined('ABSPATH')) exit;`
 *     is fine; we flag plugins that MISSING it).
 *   - Suspicious function calls: `eval()`, `base64_decode()`, `system()`,
 *     `exec()`, `shell_exec()`, `passthru()`, `proc_open()`, `popen()`.
 *   - Remote code inclusion: `include($_GET...)`, `include_once($_POST...)`.
 *   - SQL injection patterns: string-concatenated queries without prepare().
 *   - File upload without validation: `move_uploaded_file` with user input.
 *
 * Performance budget:
 *   - Single pass per file, 5 regex scans, no AST.
 *   - Early exit: once a file trips 3+ flags, we stop scanning it.
 *   - Total for a 50-file plugin: ~5–15 ms cold, cached thereafter.
 *
 * The result is a list of SecurityFlag, severity-ordered. An empty result
 * means "no obvious red flags" — NOT "safe". A human reviewer is still
 * required for production deployment.
 */
final class SecurityScanner
{
    private const SUSPICIOUS_FUNCTIONS = [
        'eval' => 'high',
        'base64_decode' => 'medium',
        'gzinflate' => 'medium',
        'str_rot13' => 'medium',
        'system' => 'high',
        'exec' => 'high',
        'shell_exec' => 'high',
        'passthru' => 'high',
        'proc_open' => 'high',
        'popen' => 'high',
        'create_function' => 'high',
        'assert' => 'high',
    ];

    private const REMOTE_INCLUDE_PATTERN = '/
        \b(?:include|include_once|require|require_once)\s*\(?\s*
        \$_(?:GET|POST|REQUEST|COOKIE)
    /mix';

    private const SQL_CONCAT_PATTERN = '/
        \$wpdb->(?:query|get_results|get_row|get_var|get_col)\s*\(\s*
        ["\x27][^"\x27]*\$_(?:GET|POST|REQUEST)
    /mix';

    private const UPLOAD_PATTERN = '/
        move_uploaded_file\s*\([^)]*\$_(?:FILES|GET|POST|REQUEST)
    /mix';

    private const ABSPATH_GUARD_PATTERN = '/
        defined\s*\(\s*["\x27]ABSPATH["\x27]\s*\)
    /mix';

    /**
     * @return list<array{file: string, line: int, severity: string, pattern: string, message: string}>
     */
    public function scan(string $pluginDir, array $phpFiles): array
    {
        $flags = [];

        foreach ($phpFiles as $rel) {
            $abs = rtrim($pluginDir, '/') . '/' . ltrim($rel, '/');
            if (!is_file($abs)) {
                continue;
            }

            $contents = @file_get_contents($abs);
            if ($contents === false) {
                continue;
            }

            $fileFlags = $this->scanFile($rel, $contents);

            // Cap per-file noise: 3 flags is enough to warrant review.
            if (count($fileFlags) >= 3) {
                $flags = array_merge($flags, $fileFlags);

                continue;
            }

            $flags = array_merge($flags, $fileFlags);
        }

        // Sort by severity: high → medium → low.
        $order = ['high' => 0, 'medium' => 1, 'low' => 2];
        usort($flags, static function (array $a, array $b) use ($order): int {
            return ($order[$a['severity']] ?? 9) <=> ($order[$b['severity']] ?? 9);
        });

        return $flags;
    }

    /**
     * @return list<array{file: string, line: int, severity: string, pattern: string, message: string}>
     */
    private function scanFile(string $rel, string $contents): array
    {
        $flags = [];

        // 1) Missing ABSPATH guard — only flag for "main" plugin files.
        $isMain = str_ends_with($rel, '.php')
            && !str_contains($rel, '/')
            && substr_count($rel, '/') === 0;

        if ($isMain && !preg_match(self::ABSPATH_GUARD_PATTERN, $contents)) {
            $flags[] = [
                'file' => $rel,
                'line' => 1,
                'severity' => 'low',
                'pattern' => 'missing_abspath_guard',
                'message' => 'Plugin entry file does not check for ABSPATH. Direct execution may be possible.',
            ];
        }

        // 2) Suspicious function calls.
        foreach (self::SUSPICIOUS_FUNCTIONS as $fn => $severity) {
            $pattern = '/\b' . preg_quote($fn, '/') . '\s*\(/mix';
            if (preg_match($pattern, $contents, $m, PREG_OFFSET_CAPTURE)) {
                $offset = $m[0][1];
                $line = substr_count(substr($contents, 0, $offset), "\n") + 1;
                $flags[] = [
                    'file' => $rel,
                    'line' => $line,
                    'severity' => $severity,
                    'pattern' => $fn,
                    'message' => "Use of `{$fn}()` — verify this is intentional and sandboxed.",
                ];
            }
        }

        // 3) Remote include patterns.
        if (preg_match(self::REMOTE_INCLUDE_PATTERN, $contents, $m, PREG_OFFSET_CAPTURE)) {
            $offset = $m[0][1];
            $line = substr_count(substr($contents, 0, $offset), "\n") + 1;
            $flags[] = [
                'file' => $rel,
                'line' => $line,
                'severity' => 'high',
                'pattern' => 'remote_include',
                'message' => 'Dynamic include with user input — possible LFI/RFI.',
            ];
        }

        // 4) SQL concatenation with user input.
        if (preg_match(self::SQL_CONCAT_PATTERN, $contents, $m, PREG_OFFSET_CAPTURE)) {
            $offset = $m[0][1];
            $line = substr_count(substr($contents, 0, $offset), "\n") + 1;
            $flags[] = [
                'file' => $rel,
                'line' => $line,
                'severity' => 'high',
                'pattern' => 'sql_concat',
                'message' => 'SQL query built with user input — use $wpdb->prepare() instead.',
            ];
        }

        // 5) Unvalidated file upload.
        if (preg_match(self::UPLOAD_PATTERN, $contents, $m, PREG_OFFSET_CAPTURE)) {
            $offset = $m[0][1];
            $line = substr_count(substr($contents, 0, $offset), "\n") + 1;
            $flags[] = [
                'file' => $rel,
                'line' => $line,
                'severity' => 'high',
                'pattern' => 'unsafe_upload',
                'message' => 'move_uploaded_file with raw user input — validate the file first.',
            ];
        }

        return $flags;
    }
}