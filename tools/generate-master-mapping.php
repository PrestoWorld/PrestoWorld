<?php

/**
 * Sinh resources/mappings/master.json từ spec 10 (WordPress Compiler).
 *
 * Spec là nguồn chuẩn — script dịch bảng Markdown của spec 10 §10.3/10.4/10.5
 * sang Master Mapping Registry JSON (§10.2.5, §10.8.1).
 *
 * Usage:
 *   php tools/generate-master-mapping.php [--spec=/path/10-wordpress-compiler.md]
 *                                          [--out=resources/mappings/master.json]
 *                                          [--strict]
 *
 * --strict: exit 1 khi còn ô cell không xử lý được.
 */

declare(strict_types=1);

const DEFAULT_SPEC = '/Users/puleeno/Projects/Specs/prestoworld/10-wordpress-compiler.md';

/** @var list<string> */
$warnings = [];

/** @var list<array<string, mixed>> */
$rules = [];

$options = getopt('', ['spec::', 'out::', 'strict']);
$specPath = is_string($options['spec'] ?? null) ? $options['spec'] : DEFAULT_SPEC;
$outPath = is_string($options['out'] ?? null)
    ? $options['out']
    : __DIR__ . '/../resources/mappings/master.json';
$strict = array_key_exists('strict', $options);

if (!is_file($specPath)) {
    fwrite(STDERR, "Spec not found: {$specPath}\n");
    exit(1);
}

$markdown = file_get_contents($specPath);
if ($markdown === false) {
    fwrite(STDERR, "Unable to read spec: {$specPath}\n");
    exit(1);
}

// ---------------------------------------------------------------------------
// Parsing helpers
// ---------------------------------------------------------------------------

/**
 * @return list<array{heading: string, header: list<string>, rows: list<list<string>>}>
 */
function parse_markdown_tables(string $markdown): array
{
    $lines = explode("\n", $markdown);
    $tables = [];
    $heading = '';
    $count = count($lines);

    for ($i = 0; $i < $count; $i++) {
        $line = $lines[$i];

        if (preg_match('/^#{2,3}\s+(.*)$/', $line, $m) === 1) {
            $heading = trim($m[1]);
            continue;
        }

        if (!str_starts_with($line, '|')) {
            continue;
        }

        if ($i + 1 >= $count || preg_match('/^\|[\s:|-]+\|$/', trim($lines[$i + 1])) !== 1) {
            continue;
        }

        $header = split_table_row($line);
        $i += 2;
        $rows = [];
        while ($i < $count && str_starts_with($lines[$i], '|')) {
            $rows[] = split_table_row($lines[$i]);
            $i++;
        }
        $i--;

        $tables[] = ['heading' => $heading, 'header' => $header, 'rows' => $rows];
    }

    return $tables;
}

/**
 * @return list<string>
 */
function split_table_row(string $line): array
{
    $cells = preg_split('/(?<!\\\\)\|/', $line);
    if ($cells === false) {
        return [];
    }

    array_shift($cells);
    array_pop($cells);

    return array_map(
        static fn (string $cell): string => str_replace('\|', '|', trim($cell)),
        $cells,
    );
}

/**
 * @param list<string> $row
 */
function cell(int $rowIndex, array $row): string
{
    return $row[$rowIndex] ?? '';
}

function section_id(string $heading): string
{
    if (preg_match('/^(10\.\d+\.\d+)/', $heading, $m) === 1) {
        return $m[1];
    }

    return '';
}

// ---------------------------------------------------------------------------
// Target resolution (§10.4.1, §10.5.1)
// ---------------------------------------------------------------------------

/**
 * @return array{skip?: bool, target?: string, wrap?: string, echo?: bool, throws?: bool}
 */
function resolve_function_target(string $cell, string $source): array
{
    $cell = trim(str_replace('`', '', $cell));

    if ($cell === '' || str_starts_with($cell, '*(')) {
        return str_contains($cell, 'đã ở') ? ['skip' => true] : ['target' => ''];
    }

    if ($cell === 'throw LegacyTerminationException') {
        return [
            'target' => 'PrestoWorld\Core\Legacy\LegacyTerminationException',
            'throws' => true,
        ];
    }

    if (str_starts_with($cell, 'json_encode')) {
        return ['target' => 'json_encode'];
    }

    if ($cell === 'false') {
        return ['target' => ''];
    }

    $wrap = null;
    if (str_contains($cell, ' + ')) {
        [$cell, $inner] = array_map('trim', explode(' + ', $cell, 2));
        $wrap = resolve_static_target($inner);
    }

    $result = ['target' => resolve_static_target($cell)];

    if ($wrap !== null) {
        $result['wrap'] = $wrap;
    }

    if (in_array($source, ['esc_html_e', 'esc_attr_e'], true)) {
        $result['echo'] = true;
        // Spec chain dùng Translator::_e, nhưng pass biên dịch tự thêm `echo`
        // ở statement level → inner phải là Translator::__ (trả chuỗi) để
        // tránh double-echo (WP: esc_html_e = echo esc_html(__($text))).
        if (isset($result['wrap']) && str_ends_with($result['wrap'], '::_e')) {
            $result['wrap'] = substr($result['wrap'], 0, -1) . '_';
        }
    }

    return $result;
}

function resolve_static_target(string $cell): string
{
    $cell = trim($cell);

    if (!str_contains($cell, '::')) {
        return $cell;
    }

    [$class, $method] = explode('::', $cell, 2);

    return resolve_class_target($class) . '::' . trim($method);
}

function resolve_class_target(string $cell): string
{
    $cell = trim(str_replace('`', '', $cell));

    if (str_starts_with($cell, 'PrestoWorld\\')) {
        return $cell;
    }

    // §2.5 file structure — các class nằm trong sub-namespace.
    $subNamespace = [
        'LegacyRegistry' => 'Legacy\LegacyRegistry',
        'LegacyInvoker' => 'Legacy\LegacyInvoker',
        'HookDiscovery' => 'Legacy\HookDiscovery',
        'PrestoWpdb' => 'Database\PrestoWpdb',
        'QueryTransformer' => 'Database\QueryTransformer',
        'DataMasker' => 'Database\DataMasker',
    ];

    if (isset($subNamespace[$cell])) {
        return 'PrestoWorld\Core\\' . $subNamespace[$cell];
    }

    return 'PrestoWorld\Core\\' . ltrim($cell, '\\');
}

// ---------------------------------------------------------------------------
// Main walk — chỉ xử lý table trong §10.3 / §10.4 / §10.5
// ---------------------------------------------------------------------------

foreach (parse_markdown_tables($markdown) as $table) {
    $heading = $table['heading'];
    $section = section_id($heading);

    if (preg_match('/^10\.[345]\./', $section) !== 1) {
        continue;
    }

    $header = $table['header'];
    $header0 = strtolower($header[0] ?? '');

    // 10.3.5 — $wpdb API → Cycle ORM
    if (str_starts_with($header0, 'wordpress')) {
        foreach ($table['rows'] as $row) {
            $raw = cell(0, $row);
            if (preg_match('/\$wpdb->(\w+)/', $raw, $m) !== 1) {
                $warnings[] = "[{$heading}] wpdb row skipped: {$raw}";
                continue;
            }

            $target = str_replace('`', '', cell(1, $row));
            if ($target === '') {
                continue;
            }

            // Target có thể liệt kê 2 phương án ("A(...) / B(...)") — lấy phương án đầu.
            $notes = null;
            if (str_contains($target, ' / ')) {
                [$target, $notes] = array_map('trim', explode(' / ', $target, 2));
            }

            $rule = [
                'kind' => 'wpdb',
                'pattern' => 'wpdb::' . $m[1],
                'replacement' => $target,
                'target' => 'any',
                'apply' => 'runtime',
                'phase' => 'api',
            ];
            if ($notes !== null) {
                $rule['notes'] = $notes;
            }
            $rules[] = $rule;
        }
        continue;
    }

    // 10.3.3 — DML: MySQL | PostgreSQL | SQLite
    if ($header === ['MySQL', 'PostgreSQL', 'SQLite']) {
        foreach ($table['rows'] as $row) {
            collect_dml_rules(cell(0, $row), cell(1, $row), cell(2, $row), $rules, $warnings);
        }
        continue;
    }

    // 10.3.1 / 10.3.2 — DDL: MySQL | Target | Ghi chú
    if ($header[0] === 'MySQL' && in_array($header[1] ?? '', ['PostgreSQL', 'SQLite'], true)) {
        $dialect = $header[1] === 'SQLite' ? 'sqlite' : 'postgresql';
        foreach ($table['rows'] as $row) {
            collect_ddl_rules(cell(0, $row), cell(1, $row), $dialect, $rules, $warnings, $heading);
        }
        continue;
    }

    // Unsupported / Deprecated (mode x): Source | Lý do | Thay thế
    if (in_array('Lý do', $header, true)) {
        foreach ($table['rows'] as $row) {
            foreach (preg_split('/,\s*/', str_replace('`', '', cell(0, $row))) ?: [] as $source) {
                $source = trim($source);
                if ($source === '') {
                    continue;
                }

                $notes = trim(cell(1, $row));
                $alternative = trim(cell(2, $row));
                $rules[] = [
                    'kind' => 'function',
                    'source' => $source,
                    'target' => '',
                    'mode' => 'x',
                    'group' => $heading,
                    'notes' => trim($notes . ($alternative !== '' ? " — thay thế: {$alternative}" : '')),
                ];
            }
        }
        continue;
    }

    // 10.4.x function groups + 10.5.x class groups: Source | Target | Mode | Ghi chú
    if (($header[0] ?? '') === 'Source' && in_array('Target', $header, true) && in_array('Mode', $header, true)) {
        $targetIndex = (int) array_search('Target', $header, true);
        $modeIndex = (int) array_search('Mode', $header, true);
        $isClass = str_starts_with($section, '10.5.');

        foreach ($table['rows'] as $row) {
            $sourceCell = cell(0, $row);
            $targetCell = cell($targetIndex, $row);
            $mode = strtolower(trim(cell($modeIndex, $row)));

            if (trim($sourceCell) === '') {
                continue;
            }

            if ($isClass) {
                collect_class_rule($sourceCell, $targetCell, $mode, $heading, $rules, $warnings);
            } else {
                collect_function_rules($sourceCell, $targetCell, $mode, $heading, $rules, $warnings);
            }
        }
        continue;
    }

    // Các bảng meta của spec (bảng mô tả) — không phải mapping table.
    if (($header[0] ?? '') === 'Source') {
        $warnings[] = "[{$heading}] unhandled Source-table header: " . implode(' | ', $header);
    }
}

// --- 10.3.4 — table mapping (code block "wp_x → pw_y") --------------------

$lines = explode("\n", $markdown);
$inBlock = false;
$inMappingSection = false;
foreach ($lines as $line) {
    if (preg_match('/^#{2,3}\s+(.*)$/', $line, $m) === 1) {
        $inMappingSection = str_contains($m[1], 'Table Mapping');
        $inBlock = false;
        continue;
    }

    if (!$inMappingSection) {
        continue;
    }

    if (str_starts_with(trim($line), '```')) {
        $inBlock = !$inBlock;
        continue;
    }

    if (!$inBlock || preg_match('/^\s*(.+?)\s+→\s+(.+)$/', $line, $m) !== 1) {
        continue;
    }

    $rawTarget = trim($m[2]);
    if (str_starts_with($rawTarget, '(')) {
        continue;
    }

    if (preg_match('/^([a-z][a-z0-9_]*(?:\.[a-z0-9_]+)?)/i', $rawTarget, $t) !== 1) {
        continue;
    }

    $target = $t[1];
    $isJsonColumn = str_contains($target, '.');

    foreach (preg_split('#\s*/\s*#', $m[1]) ?: [] as $source) {
        $source = trim($source);
        if (preg_match('/^[a-z][a-z0-9_]*$/i', $source) !== 1) {
            continue;
        }

        $rules[] = [
            'kind' => 'sql',
            'pattern' => '/\b' . preg_quote($source, '/') . '\b/i',
            'replacement' => $target,
            'target' => 'any',
            'apply' => $isJsonColumn ? 'runtime' : 'compile',
            'phase' => 'table',
            'notes' => $isJsonColumn
                ? 'meta → JSONB: cần rewrite cấu trúc JOIN (runtime QueryTransformer)'
                : null,
        ];
    }
}

// Rule thủ công: GROUP_CONCAT ... SEPARATOR (§10.3.3, cả 2 dialect).
$rules[] = [
    'kind' => 'sql',
    'pattern' => '/\s+SEPARATOR\s+/i',
    'replacement' => ', ',
    'target' => 'any',
    'apply' => 'compile',
    'phase' => 'dml',
    'notes' => "GROUP_CONCAT(x SEPARATOR ',') → STRING_AGG/GROUP_CONCAT(x, ',')",
];

// Structural adaptation cho WP_Query (§10.5.1 — mapping format example).
foreach ($rules as &$rule) {
    if (($rule['kind'] ?? '') === 'class' && ($rule['source'] ?? '') === 'WP_Query') {
        $rule['structural'] = [
            'constructor' => 'ioc',
            'static_methods' => 'instance',
            'global_state' => 'scoped',
        ];
    }
}
unset($rule);

// ---------------------------------------------------------------------------
// Collectors
// ---------------------------------------------------------------------------

/**
 * @param list<array<string, mixed>> $rules
 * @param list<string> $warnings
 */
function collect_function_rules(string $sourceCell, string $targetCell, string $mode, string $heading, array &$rules, array &$warnings): void
{
    foreach (preg_split('#\s*/\s*#', trim($sourceCell)) ?: [] as $source) {
        $source = trim($source, '` ');
        if ($source === '') {
            continue;
        }

        if (preg_match('/^[A-Za-z_*][A-Za-z0-9_.*:]*$/', $source) !== 1) {
            $warnings[] = "[{$heading}] function source skipped: {$source}";
            continue;
        }

        if (!in_array($mode, ['s', 'r', 'sr', 'n', 'x'], true)) {
            $warnings[] = "[{$heading}] {$source}: unknown mode \"{$mode}\"";
            continue;
        }

        $resolved = resolve_function_target($targetCell, $source);
        if (($resolved['skip'] ?? false) === true) {
            continue;
        }

        $rule = [
            'kind' => 'function',
            'source' => $source,
            'target' => $resolved['target'] ?? '',
            'mode' => $mode,
            'group' => $heading,
        ];

        foreach (['wrap', 'echo', 'throws'] as $extra) {
            if (isset($resolved[$extra])) {
                $rule[$extra] = $resolved[$extra];
            }
        }

        $rules[] = $rule;
    }
}

/**
 * @param list<array<string, mixed>> $rules
 * @param list<string> $warnings
 */
function collect_class_rule(string $sourceCell, string $targetCell, string $mode, string $heading, array &$rules, array &$warnings): void
{
    $targetCell = trim(str_replace('`', '', $targetCell));

    if ($targetCell === '' || str_starts_with($targetCell, '*(')) {
        if (str_contains($targetCell, 'đã ở')) {
            return; // duplicate reference (vd: WP_User_Query → 10.5.3)
        }
        $target = '';
    } else {
        $target = resolve_class_target($targetCell);
    }

    // Ô source có annotation ("WP Cron (không class)") — không phải class name.
    if (preg_match('/^`([^`]+)`/', trim($sourceCell), $m) === 1) {
        $source = trim($m[1]);
    } else {
        $source = trim($sourceCell, '` ');
    }

    if ($source === '' || preg_match('/\s|\(/', $source) === 1) {
        return; // annotation row, không phải identifier
    }

    if (preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $source) !== 1) {
        $warnings[] = "[{$heading}] class source skipped: {$source}";
        return;
    }

    if (!in_array($mode, ['s', 'r', 'sr', 'n', 'x'], true)) {
        $warnings[] = "[{$heading}] {$source}: unknown mode \"{$mode}\"";
        return;
    }

    $rules[] = [
        'kind' => 'class',
        'source' => $source,
        'target' => $target,
        'mode' => $mode,
        'group' => $heading,
    ];
}

/**
 * DDL rule override cho các ô cần xử lý đặc biệt (annotation / context-sensitive).
 *
 * @return array{pattern: string, replacement: string, apply: string, notes: ?string}|null
 */
function ddl_override(string $sourceCell, string $targetCell): ?array
{
    return match ($sourceCell) {
        'ENUM(\'a\',\'b\')' => [
            'pattern' => '/\bENUM\s*\(([^)]*)\)/i',
            'replacement' => str_replace("'a','b'", '$1', $targetCell),
            'apply' => 'runtime',
            'notes' => 'cần tên cột (context-sensitive) → runtime QueryTransformer',
        ],
        'ENUM(...)' => [
            'pattern' => '/\bENUM\s*\(([^)]*)\)/i',
            'replacement' => str_replace('...', '$1', $targetCell),
            'apply' => 'runtime',
            'notes' => 'cần tên cột (context-sensitive) → runtime QueryTransformer',
        ],
        'SET(\'a\',\'b\')' => [
            'pattern' => '/\bSET\s*\(([^)]*)\)/i',
            'replacement' => 'TEXT[]',
            'apply' => 'compile',
            'notes' => null,
        ],
        'ON UPDATE CURRENT_TIMESTAMP' => [
            'pattern' => '/\bON\s+UPDATE\s+CURRENT_TIMESTAMP\b/i',
            'replacement' => '',
            'apply' => 'compile',
            'notes' => 'PW dùng trigger updated_at',
        ],
        'DESCRIBE table' => [
            'pattern' => '/\bDESCRIBE\s+(\w+)/i',
            'replacement' => "SELECT * FROM information_schema.columns WHERE table_name = '\$1'",
            'apply' => 'compile',
            'notes' => null,
        ],
        default => null,
    };
}

/**
 * Ô không có regex rule tĩnh (chỉ xử lý được ở runtime / migration).
 */
function ddl_skip(string $sourceCell): bool
{
    // sqlite: ALTER TABLE ... ADD COLUMN cần table rebuild → migration runtime.
    return $sourceCell === 'ALTER TABLE ... ADD COLUMN';
}

/**
 * @param list<array<string, mixed>> $rules
 * @param list<string> $warnings
 */
function collect_ddl_rules(string $sourceCell, string $targetCell, string $dialect, array &$rules, array &$warnings, string $heading): void
{
    $rawSource = trim($sourceCell);
    $targetCell = trim(str_replace('`', '', $targetCell));

    if ($rawSource === '' || $targetCell === '' || $targetCell === 'Giữ nguyên') {
        return;
    }

    // Row `` `col` `` → `` "col" ``: backtick → double quote (§10.3.1/10.3.2).
    if ($targetCell === '"col"') {
        $rules[] = [
            'kind' => 'sql',
            'pattern' => '/`([^`]+)`/',
            'replacement' => '"$1"',
            'target' => $dialect,
            'apply' => 'compile',
            'phase' => 'ddl',
            'notes' => null,
        ];
        return;
    }

    $sourceCell = trim(str_replace('`', '', $rawSource));

    if (ddl_skip($sourceCell)) {
        return;
    }

    $override = ddl_override($sourceCell, $targetCell);
    if ($override !== null) {
        $rules[] = [
            'kind' => 'sql',
            'pattern' => $override['pattern'],
            'replacement' => $override['replacement'],
            'target' => $dialect,
            'apply' => $override['apply'],
            'phase' => 'ddl',
            'notes' => $override['notes'],
        ];
        return;
    }

    if (str_contains($targetCell, '→') || str_contains($targetCell, ' / ')) {
        $warnings[] = "[{$heading}] {$dialect} annotation target skipped: {$sourceCell} → {$targetCell}";
        return;
    }

    $pattern = ddl_pattern($sourceCell);
    if ($pattern === null) {
        $warnings[] = "[{$heading}] {$dialect} unhandled DDL cell: {$sourceCell}";
        return;
    }

    $rules[] = [
        'kind' => 'sql',
        'pattern' => $pattern,
        'replacement' => str_starts_with($targetCell, '*(') ? '' : $targetCell,
        'target' => $dialect,
        'apply' => 'compile',
        'phase' => 'ddl',
        'notes' => null,
    ];
}

function ddl_pattern(string $sourceCell): ?string
{
    if (str_starts_with($sourceCell, '`') && str_ends_with($sourceCell, '`') && strlen($sourceCell) > 1) {
        return '/`([^`]+)`/';
    }

    $multi = [
        'ENGINE=... CHARSET=...' => '/\s*(?:ENGINE\s*=\s*\w+|DEFAULT\s+CHARSET\s*=\s*\w+|CHARACTER\s+SET\s*=\s*\w+|COLLATE\s*=\s*\w+)/i',
        'SHOW TABLES' => '/\bSHOW\s+TABLES\b/i',
        'DATETIME/TIMESTAMP' => '/\b(?:DATETIME|TIMESTAMP)\b/i',
        'TINYINT/SMALLINT/INT/BIGINT' => '/\b(?:TINYINT|SMALLINT|INT|BIGINT)(?:\s*\(\s*\d+\s*\))?(?![_A-Z0-9])/i',
        'FOREIGN KEY ... ON DELETE CASCADE' => '/\bFOREIGN\s+KEY\b/i',
        'FOREIGN KEY ...' => '/\bFOREIGN\s+KEY\b/i',
    ];

    if (isset($multi[$sourceCell])) {
        return $multi[$sourceCell];
    }

    if (str_contains($sourceCell, '=')) {
        $name = preg_replace('/\s+/', '\s+', preg_quote(trim(strtok($sourceCell, '=')), '/'));
        if ($name === null || $name === '') {
            return null;
        }

        return '/\s*' . $name . '\s*=\s*\S+/i';
    }

    if (preg_match('/^([A-Za-z_][A-Za-z0-9_]*)\s*\(\s*([N\d,]+)\s*\)$/', $sourceCell, $m) === 1) {
        $width = str_replace('N', '\d+', preg_quote($m[2], '/'));

        return '/\b' . $m[1] . '\s*\(\s*' . $width . '\s*\)/i';
    }

    if (preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $sourceCell) === 1) {
        return '/\b' . preg_quote($sourceCell, '/') . '\b/i';
    }

    return null;
}

/**
 * @return array{pattern: ?string, replacement: ?string, apply: array<string, string>, style: string, notes: ?string}|null
 */
function dml_hand_rules(string $sourceCell): ?array
{
    $table = [
        'NOW()' => ['style' => 'call0', 'apply' => ['default' => 'compile']],
        'IFNULL(a,b)' => ['style' => 'open', 'apply' => ['default' => 'compile']],
        'RAND()' => ['style' => 'call0', 'apply' => ['default' => 'compile']],
        'FIND_IN_SET(a,b)' => [
            'style' => 'custom',
            'pattern' => '/\bFIND_IN_SET\s*\(/i',
            'apply' => ['default' => 'runtime'],
            'notes' => 'reorder args → runtime',
        ],
        'INSERT IGNORE INTO' => [
            'style' => 'custom',
            'pattern' => '/\bINSERT\s+IGNORE\s+INTO\b/i',
            'apply' => ['postgresql' => 'runtime', 'sqlite' => 'compile'],
            'notes' => 'pg: ON CONFLICT phải ở cuối câu → runtime',
        ],
        'REPLACE INTO' => [
            'style' => 'custom',
            'pattern' => '/\bREPLACE\s+INTO\b/i',
            'apply' => ['postgresql' => 'runtime', 'sqlite' => 'compile'],
            'notes' => 'pg: cần conflict target → runtime',
        ],
        'STRAIGHT_JOIN' => ['style' => 'token', 'apply' => ['default' => 'compile']],
        'LIMIT x, y' => [
            'style' => 'custom',
            'pattern' => '/\bLIMIT\s+(\d+)\s*,\s*(\d+)/i',
            'replacement' => 'LIMIT $2 OFFSET $1',
            'apply' => ['default' => 'compile'],
        ],
        'DATE_ADD(d, INTERVAL n DAY)' => [
            'style' => 'custom',
            'pattern' => '/\bDATE_ADD\s*\(/i',
            'apply' => ['default' => 'runtime'],
            'notes' => 'reorder + quote interval → runtime',
        ],
        'UNIX_TIMESTAMP()' => ['style' => 'call0', 'apply' => ['default' => 'compile']],
        'CONCAT(a,b)' => [
            'style' => 'custom',
            'pattern' => '/\bCONCAT\s*\(/i',
            'apply' => ['default' => 'runtime'],
            'notes' => 'infix (a || b) cần parse args → runtime',
        ],
        'SUBSTRING_INDEX' => [
            'style' => 'rename',
            'pattern' => '/\bSUBSTRING_INDEX\s*\(/i',
            'apply' => ['postgresql' => 'compile', 'sqlite' => 'runtime'],
            'notes' => 'sqlite: UDF/app-side',
        ],
        'LOCATE(a,b)' => [
            'style' => 'custom',
            'pattern' => '/\bLOCATE\s*\(/i',
            'apply' => ['default' => 'runtime'],
            'notes' => 'reorder args → runtime',
        ],
        'SQL_CALC_FOUND_ROWS' => [
            'style' => 'custom',
            'pattern' => '/\bSQL_CALC_FOUND_ROWS\s+/i',
            'apply' => ['default' => 'runtime'],
            'notes' => 'subquery COUNT(*) ở statement level → runtime',
        ],
        'FOUND_ROWS()' => [
            'style' => 'call0',
            'apply' => ['default' => 'runtime'],
            'notes' => 'SELECT COUNT(*) (cached) → runtime',
        ],
        '%s / %d / %f (prepare)' => [
            'style' => 'custom',
            'pattern' => '/%[sdf]/',
            'replacement' => '/* prepare placeholder → driver-native */',
            'apply' => ['default' => 'runtime'],
            'notes' => 'wpdb::prepare cấp runtime (PrestoWpdb)',
        ],
    ];

    return $table[$sourceCell] ?? null;
}

/**
 * @param list<array<string, mixed>> $rules
 * @param list<string> $warnings
 */
function collect_dml_rules(string $sourceCell, string $pgCell, string $sqliteCell, array &$rules, array &$warnings): void
{
    $sourceCell = trim(str_replace('`', '', $sourceCell));
    if ($sourceCell === '' || str_starts_with($sourceCell, "'")) {
        return; // literal datetime — giữ nguyên (§10.3.3)
    }

    if (str_starts_with($sourceCell, 'GROUP_CONCAT')) {
        foreach (['postgresql' => 'STRING_AGG(', 'sqlite' => 'GROUP_CONCAT('] as $dialect => $prefix) {
            $rules[] = [
                'kind' => 'sql',
                'pattern' => '/\bGROUP_CONCAT\s*\(/i',
                'replacement' => $prefix,
                'target' => $dialect,
                'apply' => 'compile',
                'phase' => 'dml',
                'notes' => "SEPARATOR → ', ' bởi rule riêng",
            ];
        }
        return;
    }

    $hand = dml_hand_rules($sourceCell);
    if ($hand === null) {
        $warnings[] = "DML unhandled cell: {$sourceCell}";
        return;
    }

    $targets = [
        'postgresql' => trim(str_replace('`', '', $pgCell)),
        'sqlite' => trim(str_replace('`', '', $sqliteCell)),
    ];

    foreach ($targets as $dialect => $targetCell) {
        $apply = $hand['apply'][$dialect] ?? $hand['apply']['default'];
        $pattern = $hand['pattern'] ?? null;
        $replacement = $hand['replacement'] ?? null;

        if ($pattern === null) {
            $derived = dml_pattern_and_replacement($sourceCell, $targetCell, $hand['style']);
            if ($derived === null) {
                $warnings[] = "DML cannot derive rule: {$sourceCell} ({$dialect})";
                continue;
            }
            [$pattern, $replacement] = $derived;
        }

        $rules[] = [
            'kind' => 'sql',
            'pattern' => $pattern,
            'replacement' => $replacement ?? $targetCell,
            'target' => $dialect,
            'apply' => $apply,
            'phase' => 'dml',
            'notes' => $hand['notes'] ?? null,
        ];
    }
}

/**
 * @return array{0: string, 1: string}|null
 */
function dml_pattern_and_replacement(string $sourceCell, string $targetCell, string $style): ?array
{
    if ($targetCell === '' || str_starts_with($targetCell, '*(')) {
        return null;
    }

    $openPos = strpos($sourceCell, '(');

    return match ($style) {
        'call0' => $openPos === false
            ? null
            : ['/\b' . preg_quote(substr($sourceCell, 0, $openPos), '/') . '\s*\(\s*\)/i', $targetCell],
        'open' => $openPos !== false && str_contains($targetCell, '(')
            ? [
                '/\b' . preg_quote(substr($sourceCell, 0, $openPos), '/') . '\s*\(/i',
                substr($targetCell, 0, (int) strpos($targetCell, '(') + 1),
            ]
            : null,
        'rename' => $openPos === false
            ? null
            : [
                '/\b' . preg_quote(substr($sourceCell, 0, $openPos), '/') . '\s*\(/i',
                rtrim($targetCell, ' ()') . '(',
            ],
        'token' => ['/\b' . preg_quote($sourceCell, '/') . '\b/i', $targetCell],
        default => null,
    };
}

// ---------------------------------------------------------------------------
// Dedupe + emit
// ---------------------------------------------------------------------------

$seen = [];
$unique = [];
foreach ($rules as $rule) {
    if (($rule['notes'] ?? null) === null) {
        unset($rule['notes']);
    }

    $key = implode('|', [
        (string) ($rule['kind'] ?? ''),
        (string) ($rule['source'] ?? $rule['pattern'] ?? ''),
        (string) ($rule['target'] ?? ''),
        (string) ($rule['replacement'] ?? ''),
    ]);

    if (isset($seen[$key])) {
        continue;
    }
    $seen[$key] = true;
    $unique[] = $rule;
}

$counts = ['function' => 0, 'class' => 0, 'sql' => 0, 'wpdb' => 0];
foreach ($unique as $rule) {
    $kind = (string) ($rule['kind'] ?? 'unknown');
    $counts[$kind] = ($counts[$kind] ?? 0) + 1;
}

$payload = [
    'version' => '1.0.0',
    'generated_at' => gmdate('Y-m-d\TH:i:s\Z'),
    'source_spec' => basename($specPath),
    'stats' => $counts,
    'rules' => $unique,
];

$outDir = dirname($outPath);
if (!is_dir($outDir) && !mkdir($outDir, 0775, true) && !is_dir($outDir)) {
    fwrite(STDERR, "Unable to create output dir: {$outDir}\n");
    exit(1);
}

$json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
if ($json === false) {
    fwrite(STDERR, "JSON encode failed\n");
    exit(1);
}

file_put_contents($outPath, $json . "\n");

printf(
    "Generated %s\n  functions=%d classes=%d sql=%d wpdb=%d warnings=%d\n",
    $outPath,
    $counts['function'],
    $counts['class'],
    $counts['sql'],
    $counts['wpdb'],
    count($warnings),
);

foreach ($warnings as $warning) {
    fwrite(STDERR, "  WARNING: {$warning}\n");
}

if ($strict && $warnings !== []) {
    exit(1);
}

exit(0);

