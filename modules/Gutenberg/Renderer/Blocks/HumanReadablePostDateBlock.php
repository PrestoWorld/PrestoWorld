<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\Gutenberg\Renderer\Blocks;

class HumanReadablePostDateBlock extends AbstractBlock
{
    public function render(array $context): string
    {
        $post = $context['post'] ?? [];
        $date = $post['created_at'] ?? $post['date'] ?? $post['published_at'] ?? '';
        $textColor = $this->attrs['textColor'] ?? '';
        $classes = array_merge(['wp-block-jankx-human-readable-post-date'], $this->classes);
        if ($textColor !== '') {
            $classes[] = 'has-text-color';
        }
        $classAttr = ' class="' . implode(' ', $classes) . '"';
        $styleAttr = !empty($this->styles) ? ' style="' . implode(';', $this->styles) . '"' : '';
        $inner = !empty($this->innerHTML) ? $this->innerHTML : $this->formatDate($date);
        return "<span{$classAttr}{$styleAttr}>{$inner}</span>";
    }

    protected function formatDate(string $date): string
    {
        if ($date === '') {
            return '';
        }
        try {
            $timestamp = strtotime($date);
            if ($timestamp === false) {
                return $date;
            }
            $diff = time() - $timestamp;
            if ($diff < 60) {
                return 'Just now';
            }
            if ($diff < 3600) {
                $mins = floor($diff / 60);
                return $mins . ' min ago';
            }
            if ($diff < 86400) {
                $hours = floor($diff / 3600);
                return $hours . ' hours ago';
            }
            if ($diff < 604800) {
                $days = floor($diff / 86400);
                return $days . ' days ago';
            }
            if ($diff < 2592000) {
                $weeks = floor($diff / 604800);
                return $weeks . ' weeks ago';
            }
            if ($diff < 31536000) {
                $months = floor($diff / 2592000);
                return $months . ' months ago';
            }
            $years = floor($diff / 31536000);
            return $years . ' years ago';
        } catch (\Throwable) {
            return $date;
        }
    }
}
