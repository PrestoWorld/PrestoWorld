<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\Gutenberg\Renderer\Blocks;

use PrestoWorld\Modules\Gutenberg\Renderer\Support\PostData;
use PrestoWorld\Modules\Gutenberg\Renderer\Support\QuerySupport;

/**
 * core/post-template — PHP port of the fork render callback
 * (packages/block-library/src/post-template/index.php).
 */
class PostTemplateBlock extends AbstractBlock
{
    public function render(array $context): string
    {
        $queryId   = $context['queryId'] ?? null;
        $pageKey   = ($queryId !== null && $queryId !== '')
            ? 'query-' . $queryId . '-page'
            : 'query-page';
        $page = 1;
        if (!empty($_GET[$pageKey]) && is_numeric($_GET[$pageKey])) {
            $page = max(1, (int) $_GET[$pageKey]);
        }

        $repository = $context['post_repository'] ?? null;
        if (!is_object($repository) || !method_exists($repository, 'find')) {
            return '';
        }

        $criteria = QuerySupport::buildQueryVars($this->attrs, $context, $page);
        $posts    = $repository->find($criteria);

        if (empty($posts)) {
            return '';
        }

        // Display layout classes (from the parent query block context).
        $classnames = '';
        if (isset($context['displayLayout'], $context['query']) && is_array($context['displayLayout'])) {
            if (($context['displayLayout']['type'] ?? '') === 'flex') {
                $columnCount = $context['displayLayout']['columnCount'] ?? 3;
                $classnames  = 'is-flex-container columns-' . $columnCount;
            }
            if (($context['displayLayout']['type'] ?? '') === 'grid') {
                $columnCount = $context['displayLayout']['columnCount'] ?? 3;
                $classnames  = "is-grid-container columns-{$columnCount} post-template__grid";
            }
        }

        $styleText = $this->attrs['style'] ?? $context['style'] ?? null;
        if (isset($styleText['elements']['link']['color']['text'])) {
            $classnames .= ' has-link-color';
        }

        $wrapperAttributes = $this->wrapperAttributes(['class' => trim($classnames)]);

        $content = '';
        foreach ($posts as $post) {
            $postId   = (int) PostData::id($post);
            $postType = PostData::type($post);

            $inner = '';
            foreach ($this->innerBlocks as $innerBlock) {
                $inner .= $innerBlock->render(array_merge(
                    $context,
                    ['post' => $post, 'postId' => $postId, 'postType' => $postType]
                ));
            }

            $postClasses = 'wp-block-post post-' . $postId . ' type-' . htmlspecialchars((string) $postType, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $content    .= '<li class="' . $postClasses . '">' . $inner . '</li>';
        }

        return sprintf('<ul%1$s>%2$s</ul>', $wrapperAttributes, $content);
    }
}