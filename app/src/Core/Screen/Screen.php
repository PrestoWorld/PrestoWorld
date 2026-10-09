<?php

declare(strict_types=1);

namespace PrestoWorld\Core\Screen;

/**
 * Screen — replaces WP_Screen (spec 10 §10.5).
 */
class Screen
{
    public string $id = '';
    public string $base = '';
    public string $action = '';
    public string $postType = '';
    public string $taxonomy = '';
    public string $parentFile = '';

    /** @var array<string, mixed> */
    public array $options = [];

    /** @param array<string, mixed> $args */
    public function __construct(array $args = [])
    {
        if (isset($args['id']) && is_string($args['id'])) {
            $this->id = $args['id'];
        }

        if (isset($args['base']) && is_string($args['base'])) {
            $this->base = $args['base'];
        }

        if (isset($args['action']) && is_string($args['action'])) {
            $this->action = $args['action'];
        }

        if (isset($args['post_type']) && is_string($args['post_type'])) {
            $this->postType = $args['post_type'];
        }

        if (isset($args['postType']) && is_string($args['postType'])) {
            $this->postType = $args['postType'];
        }

        if (isset($args['taxonomy']) && is_string($args['taxonomy'])) {
            $this->taxonomy = $args['taxonomy'];
        }

        if (isset($args['parent_file']) && is_string($args['parent_file'])) {
            $this->parentFile = $args['parent_file'];
        }

        if (isset($args['parentFile']) && is_string($args['parentFile'])) {
            $this->parentFile = $args['parentFile'];
        }

        if (isset($args['options']) && is_array($args['options'])) {
            foreach ($args['options'] as $key => $value) {
                if (is_string($key)) {
                    $this->options[$key] = $value;
                }
            }
        }
    }

    public function get(string $key): mixed
    {
        return match ($key) {
            'id' => $this->id,
            'base' => $this->base,
            'action' => $this->action,
            'post_type', 'postType' => $this->postType,
            'taxonomy' => $this->taxonomy,
            'parent_file', 'parentFile' => $this->parentFile,
            'options' => $this->options,
            default => $this->options[$key] ?? null,
        };
    }

    public function id(): string
    {
        return $this->id;
    }

    public function base(): string
    {
        return $this->base;
    }

    public function addOption(string $key, mixed $value): void
    {
        $this->options[$key] = $value;
    }

    /**
     * @return array<string, mixed>
     */
    public function options(): array
    {
        return $this->options;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'base' => $this->base,
            'action' => $this->action,
            'post_type' => $this->postType,
            'taxonomy' => $this->taxonomy,
            'parent_file' => $this->parentFile,
            'options' => $this->options,
        ];
    }
}
