<?php

declare(strict_types=1);

namespace PrestoWorld\Core;

use PrestoWorld\Core\Error\PrestoError;
use PrestoWorld\Core\Term\TermEntity;

/**
 * TermRepository — terms, taxonomies & object-term relationships
 * (spec 10 §10.4.16). Backing store in-memory; DB wiring là bước sau.
 */
final class TermRepository
{
    /** @var array<string, array<int, TermEntity>> */
    private static array $terms = [];

    /** @var array<int, array<string, list<int>>> objectId => taxonomy => termIds */
    private static array $relationships = [];

    private static int $nextId = 1;

    private function __construct()
    {
    }

    public static function insert(TermEntity $term): TermEntity
    {
        if ($term->term_id === 0) {
            $term->term_id = self::$nextId++;
            $term->term_taxonomy_id = $term->term_id;
        }

        self::$terms[$term->taxonomy][$term->term_id] = $term;

        return $term;
    }

    public static function find(int|string|TermEntity $term, string $taxonomy = '', string $output = 'OBJECT'): ?TermEntity
    {
        if ($term instanceof TermEntity) {
            return $term;
        }

        $taxonomies = $taxonomy !== '' ? [$taxonomy] : array_keys(self::$terms);

        foreach ($taxonomies as $tax) {
            foreach (self::$terms[$tax] ?? [] as $entity) {
                if (is_numeric($term) && $entity->term_id === (int) $term) {
                    return $entity;
                }
                if (!is_numeric($term) && ($entity->slug === $term || $entity->name === $term)) {
                    return $entity;
                }
            }
        }

        return null;
    }

    public static function exists(int|string $term, string $taxonomy = ''): int|false
    {
        $entity = self::find($term, $taxonomy);

        return $entity === null ? false : $entity->term_id;
    }

    /**
     * @param array<string, mixed> $args
     * @return list<TermEntity>
     */
    public static function query(array $args = []): array
    {
        $taxonomyRaw = $args['taxonomy'] ?? 'category';
        $taxonomy = is_scalar($taxonomyRaw) ? (string) $taxonomyRaw : 'category';
        $result = array_values(self::$terms[$taxonomy] ?? []);

        if (isset($args['slug']) && is_scalar($args['slug'])) {
            $slug = (string) $args['slug'];
            $result = array_filter($result, static fn (TermEntity $t): bool => $t->slug === $slug);
        }

        if (isset($args['name']) && is_scalar($args['name'])) {
            $name = (string) $args['name'];
            $result = array_filter($result, static fn (TermEntity $t): bool => $t->name === $name);
        }

        if (isset($args['search']) && is_scalar($args['search']) && (string) $args['search'] !== '') {
            $needle = strtolower((string) $args['search']);
            $result = array_filter($result, static fn (TermEntity $t): bool => str_contains(strtolower($t->name), $needle));
        }

        if (!empty($args['hide_empty'])) {
            $result = array_filter($result, static fn (TermEntity $t): bool => $t->count > 0);
        }

        if (isset($args['parent']) && is_numeric($args['parent'])) {
            $parent = (int) $args['parent'];
            $result = array_filter($result, static fn (TermEntity $t): bool => $t->parent === $parent);
        }

        $result = array_values($result);
        $orderby = $args['orderby'] ?? 'name';
        $order = $args['order'] ?? 'ASC';
        self::sort($result, is_scalar($orderby) ? (string) $orderby : 'name', is_scalar($order) ? (string) $order : 'ASC');

        $offsetRaw = $args['offset'] ?? 0;
        $offset = is_numeric($offsetRaw) ? (int) $offsetRaw : 0;
        if ($offset > 0) {
            $result = array_slice($result, $offset);
        }

        $numberRaw = $args['number'] ?? 0;
        $number = is_numeric($numberRaw) ? (int) $numberRaw : 0;
        if ($number > 0) {
            $result = array_slice($result, 0, $number);
        }

        return $result;
    }

    /**
     * @return list<int>
     */
    public static function children(int $termId, string $taxonomy = ''): array
    {
        $taxonomies = $taxonomy !== '' ? [$taxonomy] : array_keys(self::$terms);
        $children = [];
        foreach ($taxonomies as $tax) {
            foreach (self::$terms[$tax] ?? [] as $entity) {
                if ($entity->parent === $termId) {
                    $children[] = $entity->term_id;
                }
            }
        }

        return $children;
    }

    public static function category(int $id, string $output = 'OBJECT'): ?TermEntity
    {
        return self::find($id, 'category');
    }

    /**
     * @param array<string, mixed> $args
     * @return list<TermEntity>
     */
    public static function categories(array $args = []): array
    {
        return self::query($args + ['taxonomy' => 'category']);
    }

    /**
     * @param array<string, mixed> $args
     * @return list<TermEntity>
     */
    public static function tags(array $args = []): array
    {
        return self::query($args + ['taxonomy' => 'post_tag']);
    }

    /**
     * @param string|array<int, string> $taxonomies
     * @param array<string, mixed>      $args
     * @return list<TermEntity>
     */
    public static function objectTerms(int $objectId, string|array $taxonomies, array $args = []): array
    {
        $taxonomies = (array) $taxonomies;
        $result = [];
        foreach ($taxonomies as $taxonomy) {
            foreach (self::$relationships[$objectId][$taxonomy] ?? [] as $termId) {
                $term = self::$terms[$taxonomy][$termId] ?? null;
                if ($term !== null) {
                    $result[] = $term;
                }
            }
        }

        return $result;
    }

    /**
     * @param string|array<int, string|int> $terms
     * @return list<TermEntity>
     */
    public static function setObjectTerms(int $objectId, string|array $terms, string $taxonomy, bool $append = false): array
    {
        if (!TaxonomyRegistry::has($taxonomy)) {
            $taxonomy = 'post_tag';
        }

        $ids = [];
        foreach ((array) $terms as $term) {
            $entity = self::resolveOrCreate($term, $taxonomy);
            if ($entity === null) {
                continue;
            }
            $ids[] = $entity->term_id;
        }

        if (!$append) {
            self::detachAll($objectId, $taxonomy);
        }

        foreach ($ids as $id) {
            self::attach($objectId, $taxonomy, $id);
        }

        return self::objectTerms($objectId, [$taxonomy]);
    }

    /**
     * @param string|array<int, string|int> $terms
     * @return list<TermEntity>
     */
    public static function setPostTerms(int $postId, string|array $terms, string $taxonomy = 'post_tag', bool $append = false): array
    {
        return self::setObjectTerms($postId, $terms, $taxonomy, $append);
    }

    /**
     * @param array<string, mixed> $args
     * @return list<TermEntity>|PrestoError
     */
    public static function getPostTerms(int $postId, string $taxonomy = 'post_tag', array $args = []): array|PrestoError
    {
        if (!TaxonomyRegistry::has($taxonomy)) {
            return new PrestoError('invalid_taxonomy', 'Invalid taxonomy.');
        }

        return self::objectTerms($postId, [$taxonomy], $args);
    }

    /**
     * @param string|array<int, string|int> $terms
     */
    public static function removeObjectTerms(int $objectId, string|array $terms, string $taxonomy): bool
    {
        $ids = [];
        foreach ((array) $terms as $term) {
            $entity = self::find($term, $taxonomy);
            if ($entity !== null) {
                $ids[] = $entity->term_id;
            }
        }

        $relationship = self::$relationships[$objectId][$taxonomy] ?? [];
        self::$relationships[$objectId][$taxonomy] = array_values(array_filter(
            $relationship,
            static fn (int $id): bool => !in_array($id, $ids, true),
        ));

        self::recount($taxonomy, $ids);

        return true;
    }

    /**
     * @return list<TermEntity>|PrestoError
     */
    public static function theTerms(int $postId, string $taxonomy): array|PrestoError
    {
        $terms = self::objectTerms($postId, [$taxonomy]);

        return $terms === [] ? new PrestoError('no_terms', 'No terms found.') : $terms;
    }

    /**
     * @param int|string|array<int, int|string> $term
     */
    public static function hasTerm(int|string|array $term = '', string $taxonomy = '', mixed $post = null): bool
    {
        $currentId = $post;
        if (!is_numeric($currentId)) {
            $currentId = is_object($currentId) && isset($currentId->ID) ? $currentId->ID : PostView::getId();
        }
        $objectId = is_numeric($currentId) ? (int) $currentId : 0;
        if ($objectId === 0) {
            return false;
        }

        $taxonomies = $taxonomy !== '' ? [$taxonomy] : ['category', 'post_tag'];
        $wanted = is_array($term) ? array_map('strval', $term) : [(string) $term];

        foreach ($taxonomies as $tax) {
            foreach (self::objectTerms($objectId, [$tax]) as $entity) {
                if (in_array($entity->slug, $wanted, true) || in_array((string) $entity->term_id, $wanted, true) || in_array($entity->name, $wanted, true)) {
                    return true;
                }
            }
        }

        return false;
    }

    public static function updateCount(int $termId, string $taxonomy): void
    {
        $term = self::$terms[$taxonomy][$termId] ?? null;
        if ($term === null) {
            return;
        }

        $count = 0;
        foreach (self::$relationships as $taxonomies) {
            if (in_array($termId, $taxonomies[$taxonomy] ?? [], true)) {
                $count++;
            }
        }
        $term->count = $count;
    }

    public static function remove(int $termId, string $taxonomy): void
    {
        unset(self::$terms[$taxonomy][$termId]);

        foreach (self::$relationships as $objectId => $taxonomies) {
            if (isset($taxonomies[$taxonomy])) {
                self::$relationships[$objectId][$taxonomy] = array_values(array_filter(
                    $taxonomies[$taxonomy],
                    static fn (int $id): bool => $id !== $termId,
                ));
            }
        }
    }

    /**
     * @return array<string, array<int, TermEntity>>
     */
    public static function all(): array
    {
        return self::$terms;
    }

    public static function reset(): void
    {
        self::$terms = [];
        self::$relationships = [];
        self::$nextId = 1;
    }

    private static function resolveOrCreate(int|string $term, string $taxonomy): ?TermEntity
    {
        $entity = self::find($term, $taxonomy);
        if ($entity !== null) {
            return $entity;
        }

        if (is_numeric($term)) {
            return null;
        }

        return self::insert(new TermEntity([
            'name' => $term,
            'taxonomy' => $taxonomy,
        ]));
    }

    private static function attach(int $objectId, string $taxonomy, int $termId): void
    {
        $current = self::$relationships[$objectId][$taxonomy] ?? [];
        if (!in_array($termId, $current, true)) {
            $current[] = $termId;
            self::$relationships[$objectId][$taxonomy] = $current;
            self::updateCount($termId, $taxonomy);
        }
    }

    private static function detachAll(int $objectId, string $taxonomy): void
    {
        $former = self::$relationships[$objectId][$taxonomy] ?? [];
        unset(self::$relationships[$objectId][$taxonomy]);
        self::recount($taxonomy, $former);
    }

    /**
     * @param list<int> $termIds
     */
    private static function recount(string $taxonomy, array $termIds): void
    {
        foreach ($termIds as $termId) {
            self::updateCount($termId, $taxonomy);
        }
    }

    /**
     * @param list<TermEntity> $terms
     */
    private static function sort(array &$terms, string $orderby, string $order): void
    {
        $desc = strtoupper($order) === 'DESC';
        usort($terms, static function (TermEntity $a, TermEntity $b) use ($orderby, $desc): int {
            $cmp = match ($orderby) {
                'term_id' => $a->term_id <=> $b->term_id,
                'slug' => $a->slug <=> $b->slug,
                'count' => $a->count <=> $b->count,
                'term_group' => $a->term_group <=> $b->term_group,
                default => $a->name <=> $b->name,
            };

            return $desc ? -$cmp : $cmp;
        });
    }
}
