<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\Gutenberg\Renderer\Blocks;

use PrestoWorld\Modules\Gutenberg\Renderer\Support\PostData;

/**
 * core/post-author-name — PHP port of the fork render callback
 * (packages/block-library/src/post-author-name/index.php).
 */
class PostAuthorNameBlock extends AbstractBlock
{
    public function render(array $context): string
    {
        $post = PostData::fromContext($context);
        if ($post === null) {
            return '';
        }

        $authorName = PostData::authorName($post);
        if ($authorName === '') {
            return '';
        }

        if (!empty($this->attrs['isLink'])) {
            $authorName = sprintf(
                '<a href="%1$s" target="%2$s" class="wp-block-post-author-name__link">%3$s</a>',
                htmlspecialchars((string) ($post['author_link'] ?? '#'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
                htmlspecialchars((string) ($this->attrs['linkTarget'] ?? '_self'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
                htmlspecialchars($authorName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
            );
        } else {
            $authorName = htmlspecialchars($authorName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        }

        $classes = [];
        if (isset($this->attrs['textAlign'])) {
            $classes[] = 'has-text-align-' . $this->attrs['textAlign'];
        }
        if (isset($this->attrs['style']['elements']['link']['color']['text'])) {
            $classes[] = 'has-link-color';
        }

        $wrapperAttributes = $this->wrapperAttributes(['class' => implode(' ', $classes)]);

        return sprintf('<div%1$s>%2$s</div>', $wrapperAttributes, $authorName);
    }
}