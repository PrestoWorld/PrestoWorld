<?php

declare(strict_types=1);

namespace PrestoWorld\Core\Database;

/**
 * Che dữ liệu nhạy cảm trước khi trả kết quả SELECT ra ngoài (spec 05 §5.7 / 06).
 *
 * Mặc định các cột nhạy cảm trong danh sách sẽ bị thay bằng MASK_VALUE.
 */
final class DataMasker
{
    public const MASK_VALUE = '***';

    /** @var list<string> */
    public const SENSITIVE_COLUMNS = [
        'user_pass',
        'user_activation_key',
        'session_token',
        'application_password',
        'password',
        'pass_hash',
        'auth_key',
        'secret',
    ];

    /** @var list<string> */
    private array $masked = [];

    public function __construct(?array $masked = null)
    {
        $columns = $masked ?? self::SENSITIVE_COLUMNS;
        foreach ($columns as $column) {
            $this->add((string) $column);
        }
    }

    public function add(string $column): void
    {
        $normalized = strtolower($column);
        if (!in_array($normalized, $this->masked, true)) {
            $this->masked[] = $normalized;
        }
    }

    /** @return list<string> */
    public function maskedColumns(): array
    {
        return $this->masked;
    }

    /**
     * Che các cột nhạy cảm trong một hàng.
     *
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    public function maskRow(array $row): array
    {
        foreach ($this->masked as $column) {
            if (array_key_exists($column, $row)) {
                $row[$column] = self::MASK_VALUE;
            }
        }

        return $row;
    }

    /**
     * Che các cột nhạy cảm trên từng hàng của tập kết quả.
     *
     * @param list<array<string, mixed>> $rows
     * @return list<array<string, mixed>>
     */
    public function maskRows(array $rows): array
    {
        return array_map(fn (array $row): array => $this->maskRow($row), $rows);
    }

    public function isSensitive(string $column): bool
    {
        return in_array(strtolower($column), $this->masked, true);
    }
}