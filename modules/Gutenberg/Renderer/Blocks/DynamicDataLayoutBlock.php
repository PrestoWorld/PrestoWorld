<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\Gutenberg\Renderer\Blocks;

/**
 * jankx/dynamic-data-layout — theme block that plays the role of
 * core/query + core/post-template: it queries the posts and renders its
 * jankx/dynamic-data-template child once per post.
 *
 * It also publishes the active query to the shared renderer context so that
 * sibling core/query-pagination* blocks (which live outside this block in the
 * theme template) paginate the same result set, mirroring how core/query
 * provides context to its children.
 */
class DynamicDataLayoutBlock extends AbstractBlock
{
    public function render(array $context): string
    {
        $postType     = $this->attrs['postType'] ?? 'post';
        $layout       = $this->attrs['layout'] ?? 'grid';
        $columns      = $this->attrs['columns'] ?? 3;
        $postsPerPage = (int) ($this->attrs['postsPerPage'] ?? 9);
        $orderby      = $this->attrs['orderby'] ?? 'date';

        $query = [
            'postType' => $postType,
            'perPage'  => $postsPerPage,
            'inherit'  => true,
            'order'    => strtoupper((string) ($this->attrs['order'] ?? 'DESC')),
            'orderBy'  => $orderby,
        ];

        // Publish the query to the shared renderer context so sibling
        // pagination blocks resolve the same page count.
        if (isset($context['block_renderer']) && method_exists($context['block_renderer'], 'mergeContext')) {
            $context['block_renderer']->mergeContext(['query' => $query, 'queryId' => null]);
        }

        $page = 1;
        if (!empty($_GET['query-page']) && is_numeric($_GET['query-page'])) {
            $page = max(1, (int) $_GET['query-page']);
        }

        $posts = [];
        if (isset($context['post_repository'])) {
            $criteria = [
                'post_type' => $postType,
                'status'    => 'publish',
                'order'     => $query['order'],
                'orderBy'   => $query['orderBy'],
            ];
            if ($postsPerPage > 0) {
                $criteria['per_page'] = $postsPerPage;
                $criteria['offset']   = $postsPerPage * ($page - 1);
            }
            $posts = $context['post_repository']->find($criteria);
        }

        $inner      = '';
        $hasResults = !empty($posts);

        foreach ($this->innerBlocks as $block) {
            if ($block->name === 'jankx/dynamic-data-template' && !$hasResults) {
                continue;
            }
            $inner .= $block->render(array_merge($context, [
                'posts'   => $posts,
                'query'   => $query,
                'queryId' => null,
            ]));
        }

        $classes = array_merge(
            ['wp-block-jankx-dynamic-data-layout', 'is-layout-' . $layout],
            $this->classes
        );
        $classAttr = ' class="' . implode(' ', array_unique($classes)) . '"';
        $styleAttr = !empty($this->styles) ? ' style="' . implode(';', $this->styles) . '"' : '';

        return "<div{$classAttr}{$styleAttr}>{$inner}</div>";
    }
}