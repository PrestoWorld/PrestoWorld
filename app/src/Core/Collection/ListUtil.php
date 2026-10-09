<?php

declare(strict_types=1);

namespace PrestoWorld\Core\Collection;

/**
 * Replaces WP_List_Util — array/object list filtering, sorting and plucking.
 */
final class ListUtil
{
    private function __construct()
    {
    }

    /**
     * @param array<int, mixed> $list
     * @return array<int, mixed>
     */
    public static function filter(array $list, string $field, mixed $value): array
    {
        $result = [];
        foreach ($list as $element) {
            if (self::fieldValue($element, $field) == $value) {
                $result[] = $element;
            }
        }

        return $result;
    }

    /**
     * @param array<int, mixed> $list
     * @param array<string, mixed> $criteria
     * @return array<int, mixed>
     */
    public static function where(array $list, array $criteria): array
    {
        $result = [];
        foreach ($list as $element) {
            $matches = true;
            foreach ($criteria as $field => $value) {
                if (self::fieldValue($element, (string) $field) != $value) {
                    $matches = false;
                    break;
                }
            }

            if ($matches) {
                $result[] = $element;
            }
        }

        return $result;
    }

    /**
     * @param array<int, mixed> $list
     * @return array<int, mixed>
     */
    public static function sort(array $list, string $orderby = '', string $order = 'ASC'): array
    {
        $values = array_values($list);
        $descending = strtoupper($order) === 'DESC';

        usort($values, static function (mixed $a, mixed $b) use ($orderby, $descending): int {
            if ($orderby === '') {
                $comparison = self::compare($a, $b);
            } else {
                $comparison = self::compare(
                    self::fieldValue($a, $orderby),
                    self::fieldValue($b, $orderby),
                );
            }

            return $descending ? -$comparison : $comparison;
        });

        return $values;
    }

    /**
     * @param array<int, mixed> $list
     * @return array<int, mixed>
     */
    public static function pluck(array $list, string $field): array
    {
        $result = [];
        foreach ($list as $element) {
            $result[] = self::fieldValue($element, $field);
        }

        return $result;
    }

    /**
     * @param array<int, mixed> $list
     */
    public static function find(array $list, string $field, mixed $value): mixed
    {
        foreach ($list as $element) {
            if (self::fieldValue($element, $field) == $value) {
                return $element;
            }
        }

        return null;
    }

    private static function fieldValue(mixed $element, string $field): mixed
    {
        if (is_array($element)) {
            return $element[$field] ?? null;
        }

        if (is_object($element)) {
            $vars = get_object_vars($element);

            return $vars[$field] ?? null;
        }

        return null;
    }

    private static function compare(mixed $a, mixed $b): int
    {
        if (is_numeric($a) && is_numeric($b)) {
            return ((float) $a) <=> ((float) $b);
        }

        if (is_scalar($a) && is_scalar($b)) {
            return ((string) $a) <=> ((string) $b);
        }

        return 0;
    }
}
