<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\Gutenberg\Renderer\Blocks;

use PrestoWorld\Modules\Gutenberg\Renderer\Support\PostData;

/**
 * core/post-terms — PHP port of the fork render callback
 * (packages/block-library/src/post-terms/index.php).
 */
class PostTermsBlock extends AbstractBlock
{
    public function render(array $context): string
    {
        $post = PostData::fromContext($context);
        if ($post === null || !isset($this->attrs['term'])) {
            return '';
        }

        $taxonomy = (string) $this->attrs['term'];

        $classes = ['taxonomy-' . $taxonomy];
        if (isset($this->attrs['textAlign'])) {
            $classes[] = 'has-text-align-' . $this->attrs['textAlign'];
        }
        if (isset($this->attrs['style']['elements']['link']['color']['text'])) {
            $classes[] = 'has-link-color';
        }

        $separator = empty($this->attrs['separator']) ? ' ' : (string) $this->attrs['separator'];

        $wrapperAttributes = $this->wrapperAttributes(['class' => implode(' ', $classes)]);

        $prefix = "<div{$wrapperAttributes}>";
        if (!empty($this->attrs['prefix'])) {
            $prefix .= '<span class="wp-block-post-terms__prefix">' . $this->attrs['prefix'] . '</span>';
        }

        $suffix = '</div>';
        if (!empty($this->attrs['suffix'])) {
            $suffix = '<span class="wp-block-post-terms__suffix">' . $this->attrs['suffix'] . '</span>' . $suffix;
        }

        // Port of get_the_term_list(): links joined by the separator.
        $links = [];
        foreach (PostData::terms($post, $taxonomy) as $term) {
            $name = (string) ($term['name'] ?? '');
            $url  = (string) ($term['url'] ?? '#');
            if ($name === '') {
                continue;
            }
            $links[] = '<a href="' . htmlspecialchars($url, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '">'
                . htmlspecialchars($name, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</a>';
        }

        if (empty($links)) {
            return '';
        }

        return $prefix . implode($separator, $links) . $suffix;
    }
}
