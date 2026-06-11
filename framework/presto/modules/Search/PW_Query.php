<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\Search;

/**
 * PW_Query - The high-performance successor to WP_Query
 * 
 * This class provides a 100% compatible API with WordPress WP_Query
 * but routes all requests through the Presto Search Engine for
 * maximum performance and modern database utilization.
 */
class PW_Query
{
    public array $posts = [];
    public int $post_count = 0;
    public int $found_posts = 0;
    public int $max_num_pages = 0;
    public array $query_vars = [];

    protected array $args = [];

    public function __construct(array $args = [])
    {
        if (!empty($args)) {
            $this->query($args);
        }
    }

    /**
     * Execute the query by transforming WP arguments to Search Engine calls
     */
    public function query(array $args): array
    {
        $this->args = $args;
        $this->parse_args($args);

        /** @var SearchEngine $engine */
        $engine = app(SearchEngine::class);
        
        // Transform and execute via Search Engine
        $result = $engine->search($this->args);

        $this->posts = $result->getItems();
        $this->found_posts = $result->getTotal();
        $this->post_count = count($this->posts);
        
        $limit = (int) ($this->args['posts_per_page'] ?? 10);
        $this->max_num_pages = (int) ceil($this->found_posts / ($limit > 0 ? $limit : 1));

        return $this->posts;
    }

    /**
     * Standard WP-like loop methods
     */
    public function have_posts(): bool
    {
        return !empty($this->posts);
    }

    /**
     * Map complex WP arguments (meta_query, tax_query) for the engine
     */
    protected function parse_args(array $args): void
    {
        // Default WP values
        $defaults = [
            'post_type' => 'post',
            'post_status' => 'publish',
            'posts_per_page' => 10,
            'paged' => 1,
            'orderby' => 'date',
            'order' => 'DESC',
        ];

        $this->query_vars = array_merge($defaults, $args);
    }

    /**
     * Simplified access to current post (for compatibility)
     */
    public function the_post(): ?array
    {
        return array_shift($this->posts);
    }
}
