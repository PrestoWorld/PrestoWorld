<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\Gutenberg\Renderer\Blocks;

class SearchBlock extends AbstractBlock
{
    public function render(array $context): string
    {
        $classes = array_merge(['wp-block-search'], $this->classes);
        $classAttr = ' class="' . implode(' ', array_unique($classes)) . '"';
        $styleAttr = !empty($this->styles) ? ' style="' . implode(';', $this->styles) . '"' : '';
        $label = $this->attrs['label'] ?? 'Search';
        $placeholder = $this->attrs['placeholder'] ?? 'Search...';
        $buttonText = $this->attrs['buttonText'] ?? 'Search';
        return <<<HTML
<form role="search" method="get" action="/"{$classAttr}{$styleAttr}>
  <label class="wp-block-search__label">{$label}</label>
  <div class="wp-block-search__inside-wrapper">
    <input class="wp-block-search__input" type="search" placeholder="{$placeholder}" name="s" />
    <button class="wp-block-search__button" type="submit">{$buttonText}</button>
  </div>
</form>
HTML;
    }
}
