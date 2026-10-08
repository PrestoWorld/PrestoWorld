<?php

declare(strict_types=1);

namespace PrestoWorld\Core\Post;

use PrestoWorld\Core\PostRepository;

/**
 * PostQuery — WP_Query replacement (spec 10 §10.5 structural, mode sr).
 *
 * Nhận args WP (post_type, posts_per_page, meta_query, tax_query, date_query);
 * delegate PostRepository (DB/memory). Không cache static giữa request
 * (§10.7.2: scoped, reset sau mỗi request).
 */
class PostQuery
{
    /** @var list<PostEntity> */
    public array $posts = [];

    public ?PostEntity $post = null;

    public int $current_post = -1;

    public int $found_posts = 0;

    public int $max_num_pages = 0;

    public int $post_count = 0;

    /** @var array<string, mixed> */
    public array $query = [];

    /** @var array<string, mixed> */
    public array $query_vars = [];

    private bool $queried = false;

    /** @param array<string, mixed> $args */
    public function __construct(array $args = [])
    {
        $this->query = $args;
        $this->query_vars = $args;

        $this->query($args);
    }

    /**
     * @param array<string, mixed> $args
     */
    public function query(array $args): bool
    {
        $this->query_vars = $args;
        $this->current_post = -1;
        $this->posts = $this->fetchPosts($args);
        $this->post_count = count($this->posts);
        $this->found_posts = $this->post_count;
        $perPage = (int) ($args['posts_per_page'] ?? -1);
        $this->max_num_pages = $perPage > 0
            ? (int) ceil($this->found_posts / $perPage)
            : ($this->found_posts > 0 ? 1 : 0);
        $this->queried = true;

        return $this->post_count > 0;
    }

    /**
     * get_posts — trả posts và reset vòng lặp.
     *
     * @return list<PostEntity>
     */
    public function get_posts(): array
    {
        if (!$this->queried) {
            $this->query($this->query_vars);
        }

        return $this->posts;
    }

    public function have_posts(): bool
    {
        return $this->current_post + 1 < $this->post_count;
    }

    public function the_post(): void
    {
        $this->the_next_post();
        $this->setup_postdata($this->post);
    }

    public function rewind_posts(): void
    {
        $this->current_post = -1;
        $this->post = null;
    }

    public function next_post(): PostEntity
    {
        $this->current_post++;
        $this->post = $this->posts[$this->current_post] ?? null;

        if ($this->post === null) {
            $this->current_post--;

            return new PostEntity();
        }

        return $this->post;
    }

    public function previous_post(): PostEntity
    {
        $this->current_post--;
        $this->post = $this->posts[$this->current_post] ?? null;

        if ($this->post === null) {
            $this->current_post++;

            return new PostEntity();
        }

        return $this->post;
    }

    public function is_single(): bool
    {
        return ($this->query_vars['post_type'] ?? 'post') === 'post'
            && $this->post_count === 1;
    }

    public function is_page(): bool
    {
        return ($this->query_vars['post_type'] ?? '') === 'page';
    }

    public function is_singular(): bool
    {
        return $this->is_single() || $this->is_page();
    }

    public function is_archive(): bool
    {
        return ($this->query_vars['post_type'] ?? 'post') !== 'page'
            && !isset($this->query_vars['p'])
            && !isset($this->query_vars['page_id']);
    }

    public function is_search(): bool
    {
        return isset($this->query_vars['s']) && $this->query_vars['s'] !== '';
    }

    public function is_404(): bool
    {
        return !$this->queried || $this->post_count === 0;
    }

    public function is_sticky(int $postId = 0): bool
    {
        $id = $postId !== 0 ? $postId : $this->post?->ID ?? 0;

        return $id !== 0 && in_array($id, (array) ($this->query_vars['sticky'] ?? []), true);
    }

    /**
     * @param array<string, mixed> $args
     * @return list<PostEntity>
     */
    private function fetchPosts(array $args): array
    {
        return PostRepository::query($args);
    }

    private function setup_postdata(?PostEntity $post): void
    {
        if ($post === null) {
            return;
        }

        $GLOBALS['post'] = $post;
    }

    private function the_next_post(): void
    {
        $this->current_post++;
        $this->post = $this->posts[$this->current_post] ?? null;
    }
}

/**
 * Placeholder trả về khi chạm biên vòng lặp (đủ kiểu trả về PostEntity).
 */
final class self_dummy extends PostEntity
{
}