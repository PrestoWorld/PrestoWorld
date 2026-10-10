<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\Gutenberg\Renderer\Blocks;

use PrestoWorld\Modules\Gutenberg\Renderer\Support\PostData;

/**
 * core/post-title — PHP port of the fork render callback
 * (packages/block-library/src/post-title/index.php).
 */
class PostTitleBlock extends AbstractBlock
{
    public function render(array $context): string
    {
        $post = PostData::fromContext($context);
        if ($post === null) {
            return '';
        }

        $title = PostData::title($post);
        if ($title === '') {
            return '';
        }

        $tagName = 'h2';
        if (isset($this->attrs['level'])) {
            $tagName = 0 === (int) $this->attrs['level'] ? 'p' : 'h' . (int) $this->attrs['level'];
        }

        if (!empty($this->attrs['isLink'])) {
            $rel   = !empty($this->attrs['rel']) ? 'rel="' . htmlspecialchars((string) $this->attrs['rel'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '"' : '';
            $title = sprintf(
                '<a href="%1$s" target="%2$s" %3$s>%4$s</a>',
                htmlspecialchars(PostData::permalink($post), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
                htmlspecialchars((string) ($this->attrs['linkTarget'] ?? '_self'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
                $rel,
                htmlspecialchars($title, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
            );
        } else {
            $title = htmlspecialchars($title, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        }

        $classes = [];
        if (isset($this->attrs['textAlign'])) {
            $classes[] = 'has-text-align-' . $this->attrs['textAlign'];
        }
        if (isset($this->attrs['style']['elements']['link']['color']['text'])) {
            $classes[] = 'has-link-color';
        }

        $wrapperAttributes = $this->wrapperAttributes(['class' => implode(' ', $classes)]);

        return sprintf('<%1$s%2$s>%3$s</%1$s>', $tagName, $wrapperAttributes, $title);
    }
}
