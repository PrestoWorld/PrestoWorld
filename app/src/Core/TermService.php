<?php

declare(strict_types=1);

namespace PrestoWorld\Core;

use PrestoWorld\Core\Error\PrestoError;
use PrestoWorld\Core\Term\TermEntity;

/**
 * TermService — wp_insert_term/wp_update_term/wp_delete_term (spec 10 §10.4.16).
 */
final class TermService
{
    private function __construct()
    {
    }

    /**
     * @param array<string, mixed> $args
     * @return array{term_id: int, term_taxonomy_id: int}|PrestoError
     */
    public static function create(string $term, string $taxonomy, array $args = []): array|PrestoError
    {
        if (!TaxonomyRegistry::has($taxonomy) && !in_array($taxonomy, ['category', 'post_tag'], true)) {
            return new PrestoError('invalid_taxonomy', 'Invalid taxonomy.');
        }

        if (TermRepository::exists($term, $taxonomy) !== false) {
            return new PrestoError('term_exists', 'A term with the name provided already exists.');
        }

        $slug = $args['slug'] ?? TermEntity::slugify($term);
        $description = $args['description'] ?? '';
        $parent = $args['parent'] ?? 0;

        $entity = TermRepository::insert(new TermEntity([
            'name' => $term,
            'slug' => is_scalar($slug) ? (string) $slug : TermEntity::slugify($term),
            'description' => is_scalar($description) ? (string) $description : '',
            'parent' => is_numeric($parent) ? (int) $parent : 0,
            'taxonomy' => $taxonomy,
        ]));

        return ['term_id' => $entity->term_id, 'term_taxonomy_id' => $entity->term_taxonomy_id];
    }

    /**
     * @param array<string, mixed> $args
     * @return array{term_id: int, term_taxonomy_id: int}|PrestoError
     */
    public static function update(int $termId, string $taxonomy, array $args = []): array|PrestoError
    {
        $entity = TermRepository::find($termId, $taxonomy);
        if ($entity === null) {
            return new PrestoError('invalid_term', 'Invalid term ID.');
        }

        if (isset($args['name']) && is_scalar($args['name'])) {
            $entity->name = (string) $args['name'];
        }
        if (isset($args['slug']) && is_scalar($args['slug'])) {
            $entity->slug = (string) $args['slug'];
        }
        if (isset($args['description']) && is_scalar($args['description'])) {
            $entity->description = (string) $args['description'];
        }
        if (isset($args['parent']) && is_numeric($args['parent'])) {
            $entity->parent = (int) $args['parent'];
        }

        return ['term_id' => $entity->term_id, 'term_taxonomy_id' => $entity->term_taxonomy_id];
    }

    public static function delete(int $termId, string $taxonomy, bool $forceDefault = false): bool|PrestoError
    {
        $entity = TermRepository::find($termId, $taxonomy);
        if ($entity === null) {
            return new PrestoError('invalid_term', 'Invalid term ID.');
        }

        TermRepository::remove($termId, $taxonomy);
        MetaRepository::delete('term', $termId, '', '', true);

        return true;
    }

    public static function deleteCategory(int $id): bool|PrestoError
    {
        return self::delete($id, 'category');
    }
}
