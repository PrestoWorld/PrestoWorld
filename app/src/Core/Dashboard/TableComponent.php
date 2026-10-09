<?php

declare(strict_types=1);

namespace PrestoWorld\Core\Dashboard;

/**
 * TableComponent — replaces WP_List_Table (spec 10 §10.5).
 */
class TableComponent
{
    /** @var array<string, string> */
    private array $columns = [];

    /** @var list<array<string, mixed>> */
    private array $rows = [];

    /** @var array<string, mixed> */
    private array $args = [];

    /** @param array<string, mixed> $args */
    public function __construct(array $args = [])
    {
        $this->args = $args;

        if (isset($args['columns']) && is_array($args['columns'])) {
            $this->set_columns($args['columns']);
        }

        if (isset($args['rows']) && is_array($args['rows'])) {
            foreach ($args['rows'] as $row) {
                if (is_array($row)) {
                    $this->addRow($row);
                }
            }
        }
    }

    /**
     * @param array<mixed, mixed> $columns
     */
    public function set_columns(array $columns): void
    {
        $this->columns = [];
        foreach ($columns as $key => $label) {
            if (is_string($key)) {
                $this->columns[$key] = is_string($label) ? $label : $key;
            } elseif (is_string($label)) {
                $this->columns[$label] = $label;
            }
        }
    }

    /**
     * @param array<mixed, mixed> $columns
     */
    public function setColumns(array $columns): void
    {
        $this->set_columns($columns);
    }

    public function addColumn(string $key, string $label = ''): void
    {
        $this->columns[$key] = $label !== '' ? $label : $key;
    }

    /**
     * @return array<string, string>
     */
    public function getColumns(): array
    {
        return $this->columns;
    }

    /**
     * @param array<mixed, mixed> $row
     */
    public function addRow(array $row): void
    {
        $normalized = [];
        foreach ($row as $key => $value) {
            $normalized[(string) $key] = $value;
        }

        $this->rows[] = $normalized;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function rows(): array
    {
        return $this->rows;
    }

    public function render(): string
    {
        $html = '<table>';

        if ($this->columns !== []) {
            $html .= '<thead><tr>';
            foreach ($this->columns as $label) {
                $html .= '<th>' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</th>';
            }
            $html .= '</tr></thead>';
        }

        $html .= '<tbody>';
        foreach ($this->rows as $row) {
            $html .= '<tr>';
            foreach (array_keys($this->columns) as $key) {
                $html .= '<td>' . htmlspecialchars(self::stringify($row[$key] ?? null), ENT_QUOTES, 'UTF-8') . '</td>';
            }
            $html .= '</tr>';
        }
        $html .= '</tbody></table>';

        return $html;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'columns' => $this->columns,
            'rows' => $this->rows,
            'args' => $this->args,
        ];
    }

    private static function stringify(mixed $value): string
    {
        if (is_string($value)) {
            return $value;
        }

        if (is_bool($value)) {
            return $value ? '1' : '';
        }

        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        if ($value === null) {
            return '';
        }

        if (is_array($value)) {
            $parts = [];
            foreach ($value as $item) {
                $parts[] = self::stringify($item);
            }

            return implode(' ', $parts);
        }

        if (is_object($value) && method_exists($value, '__toString')) {
            return (string) $value;
        }

        return '';
    }
}
