<?php

declare(strict_types=1);

namespace PrestoWorld\Core\Database;

use Cycle\Database\DatabaseInterface;
use Cycle\Database\StatementInterface;
use PrestoWorld\Core\Compiler\Mapping\QueryRule;

/**
 * PrestoWpdb — shim runtime cho $wpdb (spec 10 §10.3.5 + 05 §5.1).
 *
 * - Truy cập qua PrestoWpdb::instance() (compiler emit).
 * - Mọi câu SQL chạy qua QueryTransformer (dialect đích) trước khi execute.
 * - Không bắt buộc có DB: khi chưa attach DatabaseInterface thì các method
 *   query trả về giá trị rỗng an toàn (thiết kế cho CLI/unit test).
 */
final class PrestoWpdb
{
    public const ARRAY_A = 1;
    public const ARRAY_N = 2;
    public const OBJECT = 3;
    public const OBJECT_K = 4;

    public static int $queryCount = 0;

    public string $prefix = 'pw_';
    public int $insertId = 0;
    public int $numRows = 0;
    public bool $showErrors = false;

    private ?DatabaseInterface $db;
    private readonly QueryTransformer $transformer;
    private readonly DataMasker $masker;

    private static ?self $instance = null;

    public function __construct(
        ?DatabaseInterface $db = null,
        ?QueryTransformer $transformer = null,
        string $prefix = 'pw_',
        ?DataMasker $masker = null,
    ) {
        $this->db = $db;
        $this->transformer = $transformer ?? new QueryTransformer(QueryRule::TARGET_POSTGRESQL);
        $this->prefix = $prefix;
        $this->masker = $masker ?? new DataMasker();
    }

    public static function instance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self(
                db: self::resolveDatabase(),
                transformer: QueryTransformer::fromMappingFile(self::mappingFile()),
            );
        }

        return self::$instance;
    }

    public static function resetInstance(): void
    {
        self::$instance = null;
    }

    public function setDatabase(?DatabaseInterface $db): void
    {
        $this->db = $db;
    }

    public function database(): ?DatabaseInterface
    {
        return $this->db;
    }

    public function transformer(): QueryTransformer
    {
        return $this->transformer;
    }

    public function masker(): DataMasker
    {
        return $this->masker;
    }

    public function setPrefix(string $prefix): void
    {
        $this->prefix = $prefix;
    }

    /**
     * Query tổng quát. Chuỗi SQL đi qua QueryTransformer trước khi execute.
     *
     * @param list<mixed> $params
     * @return int|false số row bị ảnh hưởng (int|false nếu thất bại / thiếu DB)
     */
    public function query(string $sql, array $params = []): int|false
    {
        if ($this->db === null) {
            return false;
        }

        self::$queryCount++;

        try {
            $this->numRows = $this->db->execute($this->transformer->transform($sql), $params);
            return $this->numRows;
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * SELECT trả về mảng các hàng (assoc/object tuỳ $output).
     *
     * @param list<mixed> $params
     * @param int $output
     * @return list<array<mixed>|object>
     */
    public function get_results(string $sql, int $output = self::OBJECT, array $params = []): array
    {
        $statement = $this->statement($sql, $params);
        if ($statement === null) {
            return [];
        }

        try {
            $rows = $statement->fetchAll();
            $this->numRows = count($rows);
        } catch (\Throwable) {
            return [];
        }

        return $this->formatRows($rows, $output);
    }

    public function get_row(string $sql, int $output = self::OBJECT, array $params = []): mixed
    {
        $statement = $this->statement($sql, $params);
        if ($statement === null) {
            return null;
        }

        try {
            $row = $statement->fetch();
        } catch (\Throwable) {
            return null;
        }

        if (!is_array($row)) {
            return null;
        }

        $this->numRows = $row === [] ? 0 : 1;

        if ($output === self::OBJECT) {
            return (object) $this->masker->maskRow($row);
        }

        return $this->masker->maskRow($row);
    }

    public function get_var(string $sql, int $column = 0, array $params = []): mixed
    {
        $statement = $this->statement($sql, $params);
        if ($statement === null) {
            return null;
        }

        try {
            $value = $statement->fetchColumn($column);
        } catch (\Throwable) {
            return null;
        }

        return $value === false ? null : $value;
    }

    public function get_col(string $sql, int $column = 0, array $params = []): array
    {
        $statement = $this->statement($sql, $params);
        if ($statement === null) {
            return [];
        }

        try {
            $rows = $statement->fetchAll();
        } catch (\Throwable) {
            return [];
        }

        $result = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $values = array_values($row);
            if (isset($values[$column])) {
                $result[] = $values[$column];
            }
        }

        $this->numRows = count($result);
        return $result;
    }

    /**
     * Tương đương $wpdb->prepare — thay placeholder %d/%s/%f bằng giá trị
     * đã escape, trả về câu SQL chuỗi (fallback khi gọi theo kiểu string).
     *
     * @param mixed $args
     */
    public function prepare(string $query, ...$args): string
    {
        $query = (string) $query;

        $placeholders = 0;
        $result = (string) preg_replace_callback('/%[dsf]/', function (array $m) use (&$args, &$placeholders): string {
            if ($args === []) {
                return $m[0];
            }

            $placeholders++;
            $value = array_shift($args);

            return match ($m[0]) {
                '%d' => (string) ((int) $value),
                '%f' => (string) ((float) $value),
                default => $this->quote((string) $value),
            };
        }, $query);

        return $result;
    }

    public function esc_like(string $value): string
    {
        $value = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
        return $value;
    }

    public function escape(string $value): string
    {
        return addslashes($value);
    }

    public function quote(string $value): string
    {
        return "'" . $this->escape($value) . "'";
    }

    /**
     * Insert đơn giản — bảng/column không cho phép tách cú pháp, dùng
     * DatabaseInterface::insert khi có DB, nếu không trả về false.
     *
     * @param array<string, mixed> $data
     * @param list<string>|null $format
     */
    public function insert(string $table, array $data, ?array $format = null): int|false
    {
        if ($this->db === null || $data === []) {
            return false;
        }

        try {
            $this->db->insert($table)->values($data)->run();
            $this->insertId = (int) $this->db->getDriver()->getLastInsertID();
            return $this->insertId;
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Update đơn giản.
     *
     * @param array<string, mixed> $values
     * @param array<string, mixed> $where
     */
    public function update(string $table, array $values, array $where, ?array $_format = null): int|false
    {
        if ($this->db === null) {
            return false;
        }

        try {
            return $this->db->update($table, $values, $where)->run();
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Delete đơn giản.
     *
     * @param array<string, mixed> $where
     */
    public function delete(string $table, array $where, ?array $_format = null): int|false
    {
        if ($this->db === null) {
            return false;
        }

        try {
            return $this->db->delete($table, $where)->run();
        } catch (\Throwable) {
            return false;
        }
    }

    public function show_errors(): void
    {
        $this->showErrors = true;
    }

    public function hide_errors(): void
    {
        $this->showErrors = false;
    }

    public function db_id(): int
    {
        return 1;
    }

    public function full_query(): string
    {
        return '';
    }

    /**
     * @param list<mixed> $params
     */
    private function statement(string $sql, array $params = []): ?StatementInterface
    {
        if ($this->db === null) {
            return null;
        }

        try {
            self::$queryCount++;
            return $this->db->query($this->transformer->transform($sql), $params);
        } catch (\Throwable) {
            return null;
        }
    }

    private static function mappingFile(): string
    {
        $paths = [
            dirname(__DIR__, 5) . '/resources/mappings/master.json',
            dirname(__DIR__, 4) . '/resources/mappings/master.json',
        ];

        foreach ($paths as $path) {
            if (is_file($path)) {
                return $path;
            }
        }

        return '';
    }

    private static function resolveDatabase(): ?DatabaseInterface
    {
        return \PrestoWorld\Core\Support\App::make(DatabaseInterface::class, null);
    }

    /**
     * @param list<array<mixed>> $rows
     * @param int $output
     * @return list<array<mixed>|object>
     */
    private function formatRows(array $rows, int $output): array
    {
        if ($output === self::OBJECT) {
            return array_map(fn (array $row): object => (object) $this->masker->maskRow($row), $rows);
        }

        if ($output === self::OBJECT_K) {
            $result = [];
            foreach ($rows as $row) {
                $key = array_keys($row)[0] ?? 0;
                $result[(string) $key] = (object) $this->masker->maskRow($row);
            }
            return $result;
        }

        if ($output === self::ARRAY_N) {
            return array_map(fn (array $row): array => array_values($row), $rows);
        }

        return array_map(fn (array $row): array => $this->masker->maskRow($row), $rows);
    }
}