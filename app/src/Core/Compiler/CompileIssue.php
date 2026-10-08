<?php

declare(strict_types=1);

namespace PrestoWorld\Core\Compiler;

/**
 * Một issue trong Compatibility Report (§10.9.1).
 */
final class CompileIssue
{
    public const UNSUPPORTED_FUNCTION = 'unsupported_function';
    public const UNRESOLVED_CLASS = 'unresolved_class';
    public const QUERY_UNPARSED = 'query_unparsed';
    public const UNSAFE_SQL = 'unsafe_sql';
    public const USER_FN_SKIPPED = 'user_fn_skipped';
    public const STRUCTURAL_CLASS = 'structural_class';
    public const PLUGGABLE_FUNCTION = 'pluggable_function';

    public function __construct(
        public readonly string $type,
        public readonly string $symbol,
        public readonly ?string $file = null,
        public readonly ?int $line = null,
        public readonly ?string $reason = null,
        public readonly ?string $fallback = null,
        public readonly ?string $group = null,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $data = [
            'type' => $this->type,
            'symbol' => $this->symbol,
        ];

        foreach ([
            'file' => $this->file,
            'line' => $this->line,
            'reason' => $this->reason,
            'fallback' => $this->fallback,
            'group' => $this->group,
        ] as $key => $value) {
            if ($value !== null) {
                $data[$key] = $value;
            }
        }

        return $data;
    }
}
