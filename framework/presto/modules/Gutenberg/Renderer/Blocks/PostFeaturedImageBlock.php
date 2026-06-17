<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\Gutenberg\Renderer\Blocks;

/**
 * Post Featured Image Block rendering core/post-featured-image
 */
class PostFeaturedImageBlock extends AbstractBlock
{
    public function render(array $context): string
    {
        $post = $context['post'] ?? [];
        
        if (!isset($post['post_id']) && !isset($post['ID']) && !isset($post['id'])) {
            return '';
        }
        
        $postId = $post['post_id'] ?? $post['ID'] ?? $post['id'];
        
        $isLink = $this->attrs['isLink'] ?? false;
        $sizeSlug = $this->attrs['sizeSlug'] ?? 'post-thumbnail';
        
        // Build image attributes
        $imgAttr = [];
        
        // Alt text - use post title if isLink
        if ($isLink) {
            $title = $post['post_title'] ?? '';
            if ($title) {
                $imgAttr['alt'] = trim(strip_tags($title));
            } else {
                $imgAttr['alt'] = "Untitled post {$postId}";
            }
        }
        
        // Extra styles for image
        $extraStyles = '';
        
        // Aspect ratio with a height set needs to override the default width/height
        if (!empty($this->attrs['aspectRatio'])) {
            $extraStyles .= 'width:100%;height:100%;';
        } elseif (!empty($this->attrs['height'])) {
            $extraStyles .= 'height:' . htmlspecialchars((string)$this->attrs['height'], ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8') . ';';
        }
        
        if (!empty($this->attrs['scale'])) {
            $extraStyles .= 'object-fit:' . htmlspecialchars((string)$this->attrs['scale'], ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8') . ';';
        }
        
        if (!empty($extraStyles)) {
            $imgAttr['style'] = $extraStyles;
        }
        
        // Get featured image URL
        $imgUrl = $post['featured_image_url'] ?? '';
        
        // If useFirstImageFromPost is true and no featured image, try to get first image from content
        if (($this->attrs['useFirstImageFromPost'] ?? false) && empty($imgUrl)) {
            $content = $post['post_content'] ?? '';
            if (preg_match('/<img[^>]+src=["\']([^"\']+)["\'][^>]*>/i', $content, $matches)) {
                $imgUrl = $matches[1];
            }
        }
        
        if (empty($imgUrl)) {
            return '';
        }
        
        // Build img tag
        $imgAttrStr = '';
        foreach ($imgAttr as $key => $value) {
            $imgAttrStr .= ' ' . $key . '="' . htmlspecialchars((string)$value, ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8') . '"';
        }
        $img = "<img src=\"" . htmlspecialchars($imgUrl, ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8') . "\"{$imgAttrStr} />";
        
        // Overlay markup
        $overlayMarkup = $this->getOverlayMarkup();
        
        // Wrap in link if isLink
        if ($isLink) {
            $linkTarget = $this->attrs['linkTarget'] ?? '_self';
            $rel = !empty($this->attrs['rel']) ? 'rel="' . htmlspecialchars($this->attrs['rel'], ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8') . '"' : '';
            $height = !empty($this->attrs['height']) ? 'style="height:' . htmlspecialchars($this->attrs['height'], ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8') . '"' : '';
            $url = $post['url'] ?? $post['link'] ?? '#';
            $img = "<a href=\"" . htmlspecialchars($url, ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8') . "\" target=\"" . htmlspecialchars($linkTarget, ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8') . "\" {$rel} {$height}>{$img}{$overlayMarkup}</a>";
        } else {
            $img = $img . $overlayMarkup;
        }
        
        // Wrapper styles
        $aspectRatio = !empty($this->attrs['aspectRatio']) ? 'aspect-ratio:' . htmlspecialchars((string)$this->attrs['aspectRatio'], ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8') . ';' : '';
        $width = !empty($this->attrs['width']) ? 'width:' . htmlspecialchars((string)$this->attrs['width'], ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8') . ';' : '';
        $height = !empty($this->attrs['height']) ? 'height:' . htmlspecialchars((string)$this->attrs['height'], ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8') . ';' : '';
        
        $wrapperStyle = '';
        if ($aspectRatio || $width || $height) {
            $wrapperStyle = $aspectRatio . $width . $height;
        }
        
        // WordPress standard: always include wp-block-post-featured-image
        $classes = array_merge(['wp-block-post-featured-image'], $this->classes);
        $classAttr = ' class="' . implode(' ', array_unique($classes)) . '"';
        $styleAttr = !empty($wrapperStyle) ? ' style="' . $wrapperStyle . '"' : '';
        if (!empty($this->styles)) {
            $escapedStyles = array_map(fn ($s) => htmlspecialchars((string)$s, ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8'), $this->styles);
            $styleAttr = ' style="' . $wrapperStyle . implode(';', $escapedStyles) . '"';
        }
        
        return "<figure{$classAttr}{$styleAttr}>{$img}</figure>";
    }
    
    private function getOverlayMarkup(): string
    {
        $hasDimBackground = isset($this->attrs['dimRatio']) && $this->attrs['dimRatio'];
        
        if (!$hasDimBackground) {
            return '';
        }
        
        $classNames = ['wp-block-post-featured-image__overlay'];
        $styles = [];
        
        // Apply dim background classes
        $classNames[] = 'has-background-dim';
        $classNames[] = "has-background-dim-{$this->attrs['dimRatio']}";
        
        // Apply overlay color
        if (isset($this->attrs['overlayColor']) && $this->attrs['overlayColor']) {
            $classNames[] = "has-{$this->attrs['overlayColor']}-background-color";
        }
        
        // Apply gradient
        $hasGradient = isset($this->attrs['gradient']) && $this->attrs['gradient'];
        $hasCustomGradient = isset($this->attrs['customGradient']) && $this->attrs['customGradient'];
        
        if ($hasGradient || $hasCustomGradient) {
            $classNames[] = 'has-background-gradient';
        }
        
        if ($hasGradient) {
            $classNames[] = "has-{$this->attrs['gradient']}-gradient-background";
        }
        
        // Apply background styles
        if ($hasCustomGradient) {
            $styles[] = 'background-image: ' . htmlspecialchars((string)$this->attrs['customGradient'], ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8') . ';';
        }
        
        if (isset($this->attrs['customOverlayColor']) && $this->attrs['customOverlayColor']) {
            $styles[] = 'background-color: ' . htmlspecialchars((string)$this->attrs['customOverlayColor'], ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8') . ';';
        }
        
        return sprintf(
            '<span class="%s" style="%s" aria-hidden="true"></span>',
            implode(' ', $classNames),
            implode(' ', $styles)
        );
    }
}
