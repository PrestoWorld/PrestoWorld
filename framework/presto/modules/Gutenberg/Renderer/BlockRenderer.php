<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\Gutenberg\Renderer;

use PrestoWorld\Modules\Gutenberg\Pattern\PatternRegistry;
use PrestoWorld\Modules\Gutenberg\Renderer\Decorators\BlockDecoratorInterface;

/**
 * Hyper-Optimized Block Renderer with Decorator Pattern support
 */
class BlockRenderer
{
    protected array $registry = [];
    protected array $decorators = [];
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

    public function addDecorator(BlockDecoratorInterface $decorator): void
    {
        $this->decorators[] = $decorator;
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

        // Apply Decorators to set attributes like classes and styles
        $block['classes'] = [];
        $block['styles'] = [];
        foreach ($this->decorators as $decorator) {
            $decorator->decorate($block);
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
        // PERFORMANCE: If static and pre-rendered, return innerHTML directly
        if (!empty($block['innerHTML']) && !in_array($name, ['core/template-part', 'core/pattern', 'core/query', 'core/post-template', 'core/group', 'core/columns', 'core/column'])) {
            return $block['innerHTML'];
        }

        $classAttr = !empty($block['classes']) ? ' class="' . implode(' ', $block['classes']) . '"' : '';
        $styleAttr = !empty($block['styles']) ? ' style="' . implode(';', $block['styles']) . '"' : '';

        switch ($name) {
            case 'core/group':
                $tag = $attrs['tagName'] ?? 'div';
                return "<{$tag}{$classAttr}{$styleAttr}>{$inner}</{$tag}>";

            case 'core/template-part':
                $slug = $attrs['slug'] ?? '';
                $tagName = $attrs['tagName'] ?? 'div';
                $path = ($this->context['theme_path'] ?? '') . "/parts/{$slug}.html";
                if (!file_exists($path)) return '';
                $content = ($this->registry['__rerender'])(file_get_contents($path));
                return "<{$tagName} class=\"wp-block-template-part\">{$content}</{$tagName}>";

            case 'core/pattern':
                $slug = $attrs['slug'] ?? '';
                $content = $this->patternRegistry->get($slug);
                return $content ? ($this->registry['__rerender'])($content) : '';

            case 'core/columns':
                return "<div{$classAttr}{$styleAttr}>{$inner}</div>";

            case 'core/column':
                return "<div{$classAttr}{$styleAttr}>{$inner}</div>";

            case 'core/site-title':
                $title = $this->context['site_title'] ?? 'PrestoWorld';
                $url   = $this->context['site_url'] ?? '/';
                return ($attrs['level'] ?? 1) === 0 
                    ? "<p{$classAttr}><a href=\"{$url}\" rel=\"home\">{$title}</a></p>"
                    : "<h1{$classAttr}><a href=\"{$url}\" rel=\"home\">{$title}</a></h1>";

            case 'core/navigation':
                if (empty($inner)) {
                    $inner = '<ul class="wp-block-navigation__container"><li class="wp-block-navigation-item"><a href="/">Home</a></li></ul>';
                }
                return "<nav{$classAttr}{$styleAttr}>{$inner}</nav>";

            case 'core/query':
                return "<div{$classAttr}{$styleAttr}>{$inner}</div>";

            case 'core/post-template':
                $output = "<ul{$classAttr}>";
                for ($i = 0; $i < 3; $i++) {
                    $output .= "<li class=\"wp-block-post\">{$inner}</li>";
                }
                $output .= "</ul>";
                return $output;

            case 'core/post-title':
                $level = $attrs['level'] ?? 2;
                return "<h{$level} class=\"wp-block-post-title\"><a href=\"#\">Sample Post Title</a></h{$level}>";

            case 'core/spacer':
                return "<div{$classAttr}{$styleAttr} aria-hidden=\"true\"></div>";

            default:
                return $block['innerHTML'] ?: $inner;
        }
    }
}
