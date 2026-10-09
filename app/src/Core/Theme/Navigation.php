<?php

declare(strict_types=1);

namespace PrestoWorld\Core\Theme;

/**
 * Navigation — replaces WP_Navigation (spec 10 §10.5).
 */
class Navigation
{
    public int $id = 0;
    public string $name = '';
    public string $slug = '';
    public string $content = '';
    public string $template = '';

    /** @param array<string, mixed> $args */
    public function __construct(array $args = [])
    {
        if (isset($args['id']) && is_int($args['id'])) {
            $this->id = $args['id'];
        } elseif (isset($args['id']) && is_numeric($args['id'])) {
            $this->id = (int) $args['id'];
        }

        if (isset($args['name']) && is_string($args['name'])) {
            $this->name = $args['name'];
        }

        if (isset($args['slug']) && is_string($args['slug'])) {
            $this->slug = $args['slug'];
        }

        if (isset($args['content']) && is_string($args['content'])) {
            $this->content = $args['content'];
        }

        if (isset($args['template']) && is_string($args['template'])) {
            $this->template = $args['template'];
        }
    }

    public function get(string $key): mixed
    {
        return match ($key) {
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'content' => $this->content,
            'template' => $this->template,
            default => null,
        };
    }

    public function set(string $key, mixed $value): void
    {
        switch ($key) {
            case 'id':
                if (is_int($value)) {
                    $this->id = $value;
                } elseif (is_numeric($value)) {
                    $this->id = (int) $value;
                }
                break;
            case 'name':
                $this->name = is_string($value) ? $value : '';
                break;
            case 'slug':
                $this->slug = is_string($value) ? $value : '';
                break;
            case 'content':
                $this->content = is_string($value) ? $value : '';
                break;
            case 'template':
                $this->template = is_string($value) ? $value : '';
                break;
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'content' => $this->content,
            'template' => $this->template,
        ];
    }
}
