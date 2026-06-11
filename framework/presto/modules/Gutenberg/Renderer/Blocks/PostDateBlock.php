<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\Gutenberg\Renderer\Blocks;

/**
 * Post Date Block rendering core/post-date
 */
class PostDateBlock extends AbstractBlock
{
    public function render(array $context): string
    {
        $post = $context['post'] ?? [];
        
        // Get datetime from attributes or post data
        $datetime = $this->attrs['datetime'] ?? $post['post_date'] ?? $post['date'] ?? date('c');
        
        if (empty($datetime)) {
            return '';
        }
        
        $timestamp = strtotime($datetime);
        
        // Format date based on format attribute
        $format = $this->attrs['format'] ?? '';
        if ($format === 'human-diff') {
            if ($timestamp > time()) {
                $formattedDate = sprintf('%s from now', human_time_diff($timestamp));
            } else {
                $formattedDate = sprintf('%s ago', human_time_diff($timestamp));
            }
        } else {
            $dateFormat = empty($format) ? 'F j, Y' : $format;
            $formattedDate = date($dateFormat, $timestamp);
        }
        
        // WordPress standard: always include wp-block-post-date
        $classes = array_merge(['wp-block-post-date'], $this->classes);
        
        // Add modified date class if displayType is modified
        if (isset($this->attrs['displayType']) && $this->attrs['displayType'] === 'modified') {
            $classes[] = 'wp-block-post-date__modified-date';
        }
        
        // Add textAlign class if present
        if (isset($this->attrs['textAlign'])) {
            $classes[] = 'has-text-align-' . $this->attrs['textAlign'];
        }
        
        // Add link color class if present
        if (isset($this->attrs['style']['elements']['link']['color']['text'])) {
            $classes[] = 'has-link-color';
        }
        
        $classAttr = ' class="' . implode(' ', array_unique($classes)) . '"';
        $styleAttr = !empty($this->styles) ? ' style="' . implode(';', $this->styles) . '"' : '';
        
        $timeTag = "<time datetime=\"{$datetime}\">{$formattedDate}</time>";
        
        $isLink = $this->attrs['isLink'] ?? true;
        if ($isLink) {
            $url = $post['url'] ?? $post['link'] ?? '#';
            $timeTag = "<a href=\"{$url}\">{$timeTag}</a>";
        }
        
        return "<div{$classAttr}{$styleAttr}>{$timeTag}</div>";
    }
}
