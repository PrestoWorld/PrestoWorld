<?php

declare(strict_types=1);

namespace PrestoWorld\Core\Theme;

/**
 * NavWalker — replaces WP_Nav_Menu_Walker (spec 10 §10.5).
 */
class NavWalker
{
    /** @var array<string, mixed> */
    private array $args = [];

    /** @param array<string, mixed> $args */
    public function __construct(array $args = [])
    {
        $this->args = $args;
    }

    /**
     * @param array<mixed, mixed>|object $items
     * @param array<string, mixed> $args
     */
    public function walk(array|object $items, int $depth, array $args = []): string
    {
        $list = is_object($items) ? get_object_vars($items) : $items;
        $args = array_merge($this->args, $args);

        return $this->renderList($list, $depth, $args);
    }

    /**
     * @param array<string, mixed> $args
     */
    public function startLvl(int $output, int $depth, array $args = []): string
    {
        $class = $depth > 0 ? 'sub-menu' : 'menu';

        return '<ul class="' . $class . '">';
    }

    /**
     * @param array<string, mixed> $args
     */
    public function endLvl(int $output, int $depth, array $args = []): string
    {
        return '</ul>';
    }

    /**
     * @param array<mixed, mixed> $item
     * @param array<string, mixed> $args
     */
    public function startEl(string $output, array $item = [], int $depth = 0, array $args = []): string
    {
        $data = $this->normalizeItem($item);
        $title = htmlspecialchars($data['title'], ENT_QUOTES, 'UTF-8');
        $url = htmlspecialchars($data['url'], ENT_QUOTES, 'UTF-8');

        return $output . '<li class="menu-item"><a href="' . $url . '">' . $title . '</a>';
    }

    /**
     * @param array<mixed, mixed>|object $item
     */
    public function endEl(string $output, object|array $item, int $depth = 0): string
    {
        return $output . '</li>';
    }

    /**
     * @param array<mixed, mixed> $items
     * @param array<string, mixed> $args
     */
    private function renderList(array $items, int $depth, array $args): string
    {
        $nodes = [];
        foreach ($items as $item) {
            if (is_array($item) || is_object($item)) {
                $nodes[] = $item;
            }
        }

        if ($nodes === []) {
            return '';
        }

        $out = $this->startLvl(0, $depth, $args);
        foreach ($nodes as $item) {
            $data = $this->normalizeItem($item);
            $out = $this->startEl($out, $data, $depth, $args);

            if ($data['children'] !== []) {
                $out .= $this->renderList($data['children'], $depth + 1, $args);
            }

            $out = $this->endEl($out, $data, $depth);
        }
        $out .= $this->endLvl(0, $depth, $args);

        return $out;
    }

    /**
     * @return array{title: string, url: string, children: array<mixed, mixed>}
     */
    private function normalizeItem(mixed $item): array
    {
        $title = '';
        $url = '';
        $children = [];

        if (is_array($item)) {
            if (isset($item['title']) && is_string($item['title'])) {
                $title = $item['title'];
            }
            if (isset($item['url']) && is_string($item['url'])) {
                $url = $item['url'];
            }
            if (isset($item['children']) && is_array($item['children'])) {
                $children = $item['children'];
            }
        } elseif (is_object($item)) {
            $vars = get_object_vars($item);
            if (isset($vars['title']) && is_string($vars['title'])) {
                $title = $vars['title'];
            }
            if (isset($vars['url']) && is_string($vars['url'])) {
                $url = $vars['url'];
            }
            if (isset($vars['children']) && is_array($vars['children'])) {
                $children = $vars['children'];
            }
        }

        return ['title' => $title, 'url' => $url, 'children' => $children];
    }
}
