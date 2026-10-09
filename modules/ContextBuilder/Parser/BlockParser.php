<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\ContextBuilder\Parser;

/**
 * BlockParser — parse HTML templates with Gutenberg block comments.
 *
 * Parses templates like:
 *   <!-- wp:template-part {"slug":"header"} /-->
 *   <!-- wp:group {"tagName":"main"} -->
 *     <main>...</main>
 *   <!-- /wp:group -->
 *
 * Returns array of block data structures compatible with BlockRenderer.
 */
class BlockParser
{
    /**
     * Parse HTML template content into block array.
     *
     * @param string $html
     * @return array<int, array<string, mixed>>
     */
    public static function parse(string $html): array
    {
        $blocks = [];
        $position = 0;
        $length = strlen($html);

        while ($position < $length) {
            // Find next block comment
            $commentStart = strpos($html, '<!-- wp:', $position);
            if ($commentStart === false) {
                // No more blocks, remaining is text
                $text = substr($html, $position);
                if (trim($text) !== '') {
                    $blocks[] = [
                        'blockName' => null,
                        'attrs' => [],
                        'innerHTML' => $text,
                        'innerBlocks' => [],
                    ];
                }
                break;
            }

            // Text before block
            if ($commentStart > $position) {
                $text = substr($html, $position, $commentStart - $position);
                if (trim($text) !== '') {
                    $blocks[] = [
                        'blockName' => null,
                        'attrs' => [],
                        'innerHTML' => $text,
                        'innerBlocks' => [],
                    ];
                }
            }

            // Parse block comment
            $commentEnd = strpos($html, '-->', $commentStart);
            if ($commentEnd === false) {
                throw new \RuntimeException('Unclosed block comment at position ' . $position);
            }

            $comment = substr($html, $commentStart + 4, $commentEnd - $commentStart - 4); // Remove <!-- and -->
            $comment = trim($comment);

            if (str_starts_with($comment, '/wp:')) {
                // Closing tag - this shouldn't happen in well-formed template
                // Skip for now
                $position = $commentEnd + 3;
                continue;
            }

            // Parse block attributes from comment
            $block = self::parseBlockComment($comment);
            if ($block === null) {
                $position = $commentEnd + 3;
                continue;
            }

            // Find closing tag for this block
            $blockName = $block['blockName'];
            $closingTag = "<!-- /wp:$blockName -->";
            $closingPos = strpos($html, $closingTag, $commentEnd);

            if ($closingPos !== false) {
                // Block has inner content
                $innerStart = $commentEnd + 3;
                $innerEnd = $closingPos;
                $innerHtml = substr($html, $innerStart, $innerEnd - $innerStart);

                // Parse inner blocks from inner HTML
                $innerBlocks = self::parseInnerBlocks($innerHtml);
                $block['innerBlocks'] = $innerBlocks;
                $block['innerHTML'] = $innerHtml;

                $position = $closingPos + strlen($closingTag);
            } else {
                // Self-closing or void block
                $position = $commentEnd + 3;
                $block['innerBlocks'] = [];
                $block['innerHTML'] = '';
            }

            $blocks[] = $block;
        }

        return $blocks;
    }

    /**
     * Parse inner blocks from inner HTML.
     * This handles the case where inner HTML contains both wrapper elements and inner blocks.
     *
     * @param string $innerHtml
     * @return array<int, array<string, mixed>>
     */
    protected static function parseInnerBlocks(string $innerHtml): array
    {
        $blocks = [];
        $position = 0;
        $length = strlen($innerHtml);

        while ($position < $length) {
            // Find next block comment
            $commentStart = strpos($innerHtml, '<!-- wp:', $position);
            if ($commentStart === false) {
                // No more blocks, remaining is text (wrapper HTML)
                break;
            }

            // Text before block (wrapper HTML like <main>)
            if ($commentStart > $position) {
                $text = substr($innerHtml, $position, $commentStart - $position);
                // Skip whitespace-only text
                if (trim($text) !== '') {
                    // This is wrapper HTML, not a block - skip it
                    // The wrapper HTML is stored in innerHTML of the parent block
                }
            }

            // Parse block comment
            $commentEnd = strpos($innerHtml, '-->', $commentStart);
            if ($commentEnd === false) {
                break;
            }

            $comment = substr($innerHtml, $commentStart + 4, $commentEnd - $commentStart - 4);
            $comment = trim($comment);

            if (str_starts_with($comment, '/wp:')) {
                // Closing tag
                $position = $commentEnd + 3;
                continue;
            }

            // Parse block attributes from comment
            $block = self::parseBlockComment($comment);
            if ($block === null) {
                $position = $commentEnd + 3;
                continue;
            }

            // Find closing tag for this block
            $blockName = $block['blockName'];
            $closingTag = "<!-- /wp:$blockName -->";
            $closingPos = strpos($innerHtml, $closingTag, $commentEnd);

            if ($closingPos !== false) {
                // Block has inner content
                $innerStart = $commentEnd + 3;
                $innerEnd = $closingPos;
                $innerHtmlContent = substr($innerHtml, $innerStart, $innerEnd - $innerStart);

                // Recursively parse inner blocks
                $block['innerBlocks'] = self::parseInnerBlocks($innerHtmlContent);
                $block['innerHTML'] = $innerHtmlContent;

                $position = $closingPos + strlen($closingTag);
            } else {
                // Self-closing or void block
                $position = $commentEnd + 3;
                $block['innerBlocks'] = [];
                $block['innerHTML'] = '';
            }

            $blocks[] = $block;
        }

        return $blocks;
    }

    /**
     * Parse a single block comment like "wp:group {"tagName":"main"}" or "wp:template-part {"slug":"header"} /"
     *
     * @param string $comment
     * @return array<string, mixed>|null
     */
    protected static function parseBlockComment(string $comment): ?array
    {
        // Match wp:block-name {json} or wp:block-name {json} /
        $pattern = '/^wp:([\w\/-]+)\s*(?:\{([^}]*)\})?(?:\s*\/\s*)?$/';
        if (!preg_match($pattern, $comment, $matches)) {
            return null;
        }

        $blockName = $matches[1];
        $attrsJson = $matches[2] ?? '{}';

        $attrs = [];
        if (trim($attrsJson) !== '') {
            try {
                $attrs = json_decode($attrsJson, true, 512, JSON_THROW_ON_ERROR);
            } catch (\JsonException) {
                $attrs = [];
            }
        }

        return [
            'blockName' => $blockName,
            'attrs' => $attrs,
            'innerHTML' => '',
            'innerBlocks' => [],
        ];
    }

    /**
     * Parse template file.
     *
     * @param string $templatePath
     * @return array<int, array<string, mixed>>
     */
    public static function parseFile(string $templatePath): array
    {
        if (!is_file($templatePath)) {
            throw new \RuntimeException("Template file not found: $templatePath");
        }

        $html = file_get_contents($templatePath);
        if ($html === false) {
            throw new \RuntimeException("Cannot read template file: $templatePath");
        }

        return self::parse($html);
    }
}