<?php

declare(strict_types=1);

namespace PrestoWorld\Core\Post;

/**
 * PostEntity — WP_Post-like (spec 10 §10.5).
 */
class PostEntity
{
    public int $ID = 0;
    public string $post_title = '';
    public string $post_content = '';
    public string $post_excerpt = '';
    public string $post_status = 'publish';
    public string $post_type = 'post';
    public string $post_name = '';
    public int $post_author = 0;
    public string $post_date = '';
    public string $post_date_gmt = '';
    public string $post_modified = '';
    public string $post_modified_gmt = '';
    public int $comment_count = 0;
    public int $menu_order = 0;
    public int $post_parent = 0;
    public string $guid = '';
    public string $comment_status = 'open';
    public string $ping_status = 'open';
    public string $post_password = '';
    public string $filter = 'raw';
    public string $post_category = '';

    /** @var array<string, mixed> */
    private array $meta = [];

    /** @param array<string, mixed> $data */
    public function __construct(array $data = [])
    {
        foreach ($data as $key => $value) {
            if (property_exists($this, (string) $key)) {
                $this->{$key} = $value;
            }
        }

        if ($this->ID === 0 && isset($data['id'])) {
            $this->ID = (int) $data['id'];
        }
    }

    public function __get(string $name): mixed
    {
        return $this->meta[$name] ?? null;
    }

    public function __set(string $name, mixed $value): void
    {
        $this->meta[$name] = $value;
    }

    public function __isset(string $name): bool
    {
        return isset($this->meta[$name]);
    }

    public function get(string $field, mixed $default = null): mixed
    {
        if (property_exists($this, $field)) {
            return $this->{$field};
        }

        return $this->meta[$field] ?? $default;
    }

    /** @return array<string, mixed> */
    public function to_array(): array
    {
        return [
            'ID' => $this->ID,
            'post_title' => $this->post_title,
            'post_content' => $this->post_content,
            'post_excerpt' => $this->post_excerpt,
            'post_status' => $this->post_status,
            'post_type' => $this->post_type,
            'post_name' => $this->post_name,
            'post_author' => $this->post_author,
            'post_date' => $this->post_date,
            'comment_count' => $this->comment_count,
        ];
    }
}