<?php

declare(strict_types=1);

namespace PrestoWorld\Core\Legacy;

use PrestoWorld\Core\Database\PrestoWpdb;

/**
 * WPQuery — shim đối tượng $wp_query (spec 06 §6.2.2, §6.5.2).
 *
 * Bọc PrestoWpdb để thực thi query như WP_Query cơ bản:
 * - query(): chạy $wpdb->get_results và giữ kết quả.
 * - have_posts()/the_post()/next_post(): vòng lặp trong theme/plugin WP.
 *
 * Đây là shim tối thiểu (correctness), không phải full WP_Query engine.
 */
class WPQuery
{
    /** @var list<object> */
    private array $posts = [];

    private int $index = 0;

    private ?object $currentPost = null;

    /**
     * @param list<mixed> $params
     * @return list<object>
     */
    public function query(string $query, array $params = []): array
    {
        $wpdb = PrestoWpdb::instance();

        $rows = $wpdb->get_results($query, PrestoWpdb::OBJECT, $params);

        $this->posts = array_values(array_filter($rows, static fn (mixed $row): bool => is_object($row)));
        $this->index = 0;
        $this->currentPost = $this->posts[0] ?? null;

        if ($this->currentPost !== null) {
            $GLOBALS['post'] = $this->currentPost;
        }

        return $this->posts;
    }

    public function have_posts(): bool
    {
        return isset($this->posts[$this->index]);
    }

    public function the_post(): ?object
    {
        $post = $this->posts[$this->index] ?? null;
        $this->index++;
        $this->currentPost = $post;

        if ($post !== null) {
            $GLOBALS['post'] = $post;
        }

        return $post;
    }

    public function next_post(): ?object
    {
        return $this->the_post();
    }

    public function get_post(): ?object
    {
        return $this->currentPost;
    }

    public function rewind_posts(): void
    {
        $this->index = 0;
        $this->currentPost = $this->posts[0] ?? null;
    }

    /** @return list<object> */
    public function get_posts(): array
    {
        return $this->posts;
    }

    public function post_count(): int
    {
        return count($this->posts);
    }
}