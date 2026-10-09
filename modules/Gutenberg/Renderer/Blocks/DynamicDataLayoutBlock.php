<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\Gutenberg\Renderer\Blocks;

class DynamicDataLayoutBlock extends AbstractBlock
{
    public function render(array $context): string
    {
        $postType = $this->attrs['postType'] ?? 'post';
        $layout = $this->attrs['layout'] ?? 'grid';
        $columns = $this->attrs['columns'] ?? 3;
        $postsPerPage = $this->attrs['postsPerPage'] ?? 9;
        $orderby = $this->attrs['orderby'] ?? 'date';

        $posts = [];
        if (isset($context['post_repository'])) {
            $criteria = [
                'post_type' => $postType,
                'status' => 'publish',
            ];
            $posts = $context['post_repository']->find($criteria);
            if ($postsPerPage > 0 && count($posts) > $postsPerPage) {
                $posts = array_slice($posts, 0, $postsPerPage);
            }
        }

        $inner = '';
        $hasResults = !empty($posts);

        foreach ($this->innerBlocks as $block) {
            $name = $block->name;
            if ($name === 'jankx/dynamic-data-template' && !$hasResults) {
                continue;
            }
            $inner .= $block->render(array_merge($context, ['posts' => $posts]));
        }

        $classes = array_merge(
            ['wp-block-jankx-dynamic-data-layout', 'is-layout-' . $layout],
            $this->classes
        );
        $classAttr = ' class="' . implode(' ', $classes) . '"';
        $styleAttr = !empty($this->styles) ? ' style="' . implode(';', $this->styles) . '"' : '';

        return "<div{$classAttr}{$styleAttr}>{$inner}</div>";
    }
}
