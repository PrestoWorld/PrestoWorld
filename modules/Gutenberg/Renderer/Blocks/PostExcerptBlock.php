<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\Gutenberg\Renderer\Blocks;

use PrestoWorld\Modules\Gutenberg\Renderer\Support\PostData;

/**
 * core/post-excerpt — PHP port of the fork render callback
 * (packages/block-library/src/post-excerpt/index.php).
 */
class PostExcerptBlock extends AbstractBlock
{
    public function render(array $context): string
    {
        $post = PostData::fromContext($context);
        if ($post === null) {
            return '';
        }

        $permalink   = PostData::permalink($post);
        $moreText    = !empty($this->attrs['moreText'])
            ? '<a class="wp-block-post-excerpt__more-link" href="' . htmlspecialchars($permalink, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '">'
                . (string) $this->attrs['moreText'] . '</a>'
            : '';

        $excerpt = PostData::excerpt($post);

        $excerptLength = $this->attrs['excerptLength'] ?? null;
        if ($excerptLength !== null) {
            $excerpt = self::trimWords($excerpt, (int) $excerptLength);
        }

        if ($excerpt === '') {
            return '';
        }

        $classes = [];
        if (isset($this->attrs['textAlign'])) {
            $classes[] = 'has-text-align-' . $this->attrs['textAlign'];
        }
        if (isset($this->attrs['style']['elements']['link']['color']['text'])) {
            $classes[] = 'has-link-color';
        }

        $wrapperAttributes = $this->wrapperAttributes(['class' => implode(' ', $classes)]);

        $content = '<p class="wp-block-post-excerpt__excerpt">' . $excerpt;
        $showMoreOnNewLine = !isset($this->attrs['showMoreOnNewLine']) || $this->attrs['showMoreOnNewLine'];

        if ($showMoreOnNewLine && $moreText !== '') {
            $content .= '</p><p class="wp-block-post-excerpt__more-text">' . $moreText . '</p>';
        } elseif ($moreText === '') {
            $content .= '</p>';
        } else {
            $separator = $excerpt === '' ? '' : ' ';
            $content  .= $separator . $moreText . '</p>';
        }

        return sprintf('<div%1$s>%2$s</div>', $wrapperAttributes, $content);
    }

    /**
     * Port of wp_trim_words(): trims text to the given number of words.
     */
    protected static function trimWords(string $text, int $numWords): string
    {
        $numWords = max(0, $numWords);
        if (mb_strlen($text) === 0) {
            return '';
        }

        $words = preg_split('/\s+/', trim(strip_tags($text)));
        if ($words === false || count($words) <= $numWords) {
            return $text;
        }

        return implode(' ', array_slice($words, 0, $numWords)) . '…';
    }
}