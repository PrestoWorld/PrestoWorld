<?php

declare(strict_types=1);

namespace PrestoWorld\Core\Term;

/**
 * TermEntity — WP_Term-like (spec 10 §10.5).
 */
class TermEntity
{
    public int $term_id = 0;
    public string $name = '';
    public string $slug = '';
    public int $term_group = 0;
    public int $term_taxonomy_id = 0;
    public string $taxonomy = '';
    public string $description = '';
    public int $parent = 0;
    public int $count = 0;
    public int $object_id = 0;

    /** @param array<string, mixed> $data */
    public function __construct(array $data = [])
    {
        foreach ($data as $key => $value) {
            if (property_exists($this, (string) $key)) {
                $this->{$key} = $value;
            }
        }

        if ($this->term_taxonomy_id === 0) {
            $this->term_taxonomy_id = $this->term_id;
        }

        if ($this->slug === '' && $this->name !== '') {
            $this->slug = self::slugify($this->name);
        }
    }

    public static function slugify(string $name): string
    {
        $slug = preg_replace('/[^A-Za-z0-9]+/', '-', strtolower($name));

        return trim($slug ?? '', '-');
    }

    /**
     * @return array<string, mixed>
     */
    public function to_array(): array
    {
        return [
            'term_id' => $this->term_id,
            'name' => $this->name,
            'slug' => $this->slug,
            'term_group' => $this->term_group,
            'term_taxonomy_id' => $this->term_taxonomy_id,
            'taxonomy' => $this->taxonomy,
            'description' => $this->description,
            'parent' => $this->parent,
            'count' => $this->count,
        ];
    }
}
