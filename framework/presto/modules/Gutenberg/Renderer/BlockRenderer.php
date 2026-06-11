<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\Gutenberg\Renderer;

use PrestoWorld\Modules\Gutenberg\Pattern\PatternRegistry;

/**
 * Hyper-Optimized Block Renderer
 */
class BlockRenderer
{
    protected array $registry = [];
    protected ?PatternRegistry $patternRegistry = null;
    protected array $context = [];

    public function setPatternRegistry(PatternRegistry $registry): void
    {
        $this->patternRegistry = $registry;
    }

    public function setContext(array $context): void
    {
        $this->context = $context;
    }

    public function register(string $name, callable $callback): void
    {
        $this->registry[$name] = $callback;
    }

    public function render(array $blocks): string
    {
        $output = '';
        foreach ($blocks as $block) {
            $output .= $this->renderBlock($block);
        }
        return $output;
    }

    public function renderBlock(array &$block): string
    {
        $name = $block['blockName'];

        if ($name === null) {
            return $block['innerHTML'];
        }

        $innerHtml = '';
        if (!empty($block['innerBlocks'])) {
            foreach ($block['innerBlocks'] as &$innerBlock) {
                $innerHtml .= $this->renderBlock($innerBlock);
            }
        }

        if (isset($this->registry[$name])) {
            return ($this->registry[$name])($block['attrs'], $innerHtml, $block);
        }

        return $this->renderCoreBlock($name, $block['attrs'], $innerHtml, $block);
    }

    protected function renderCoreBlock(string $name, array &$attrs, string &$inner, array &$block): string
    {
        // PERFORMANCE: Prefer pre-rendered innerHTML for static blocks to avoid double tags and save CPU
        if (!empty($block['innerHTML']) && !in_array($name, ['core/template-part', 'core/pattern', 'core/query', 'core/post-template', 'core/group'])) {
            return $block['innerHTML'];
        }

        switch ($name) {
            case 'core/group':
                $tag   = $attrs['tagName'] ?? 'div';
                $class = $this->buildClass('wp-block-group', $attrs);
                $style = $this->buildStyle($attrs);
                return "<{$tag} class=\"{$class}\"{$style}>{$inner}</{$tag}>";

            case 'core/template-part':
                $slug = $attrs['slug'] ?? '';
                $path = ($this->context['theme_path'] ?? '') . "/parts/{$slug}.html";
                if (!file_exists($path)) return '';
                return ($this->registry['__rerender'])(file_get_contents($path));

            case 'core/pattern':
                $slug = $attrs['slug'] ?? '';
                $content = $this->patternRegistry->get($slug);
                return $content ? ($this->registry['__rerender'])($content) : '';

            case 'core/columns':
                $class = $this->buildClass('wp-block-columns', $attrs);
                return "<div class=\"{$class}\">{$inner}</div>";

            case 'core/column':
                $style = isset($attrs['width']) ? " style=\"flex-basis:{$attrs['width']}\"" : '';
                return "<div class=\"wp-block-column\"{$style}>{$inner}</div>";

            case 'core/site-title':
                $title = $this->context['site_title'] ?? 'PrestoWorld';
                $url   = $this->context['site_url'] ?? '/';
                return ($attrs['level'] ?? 1) === 0 
                    ? "<a href=\"{$url}\" class=\"wp-block-site-title\">{$title}</a>"
                    : "<h1 class=\"wp-block-site-title\"><a href=\"{$url}\">{$title}</a></h1>";

            case 'core/navigation':
                if (empty($inner)) {
                    $inner = '<li class="wp-block-navigation-item"><a href="/">Home</a></li><li class="wp-block-navigation-item"><a href="/blog">Blog</a></li>';
                }
                return "<nav class=\"wp-block-navigation\"><ul class=\"wp-block-navigation__container\">{$inner}</ul></nav>";

            case 'core/query':
                return "<div class=\"wp-block-query\">{$inner}</div>";

            case 'core/post-template':
                return str_repeat("<div class=\"wp-block-post-template\">{$inner}</div>", 3);

            case 'core/spacer':
                return "<div style=\"height:{$attrs['height']}\" aria-hidden=\"true\" class=\"wp-block-spacer\"></div>";

            default:
                return $block['innerHTML'] ?: $inner;
        }
    }

    protected function buildClass(string $base, array &$attrs): string
    {
        $classes = $base ? [$base] : [];
        if (!empty($attrs['align'])) $classes[] = 'align' . $attrs['align'];
        if (!empty($attrs['fontSize'])) $classes[] = 'has-' . $attrs['fontSize'] . '-font-size';
        if (!empty($attrs['backgroundColor'])) $classes[] = 'has-background has-' . $attrs['backgroundColor'] . '-background-color';
        return implode(' ', $classes);
    }

    protected function buildStyle(array &$attrs): string
    {
        if (empty($attrs['style'])) return '';
        
        $styles = [];
        $spacing = $attrs['style']['spacing'] ?? [];
        
        foreach (['top', 'bottom', 'left', 'right'] as $side) {
            if (isset($spacing['padding'][$side])) {
                $styles[] = "padding-{$side}:" . $this->resolveVar($spacing['padding'][$side]);
            }
            if (isset($spacing['margin'][$side])) {
                $styles[] = "margin-{$side}:" . $this->resolveVar($spacing['margin'][$side]);
            }
        }

        return $styles ? ' style="' . implode(';', $styles) . '"' : '';
    }

    protected function resolveVar(string $value): string
    {
        if ($value[0] === 'v' && str_starts_with($value, 'var:')) {
            return 'var(--wp--' . str_replace(['var:', '|'], ['', '--'], $value) . ')';
        }
        return $value;
    }
}
