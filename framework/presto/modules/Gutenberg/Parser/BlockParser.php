<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\Gutenberg\Parser;

/**
 * Ultra-High-Performance Gutenberg Parser
 * 
 * Optimized for speed:
 * - Minimal regex usage (only for attributes)
 * - Single-pass scanning with strpos
 * - Reference-based tree construction
 */
class BlockParser
{
    public function parse(string $html): array
    {
        $blocks = [];
        $stack  = [];
        $cursor = 0;
        $length = strlen($html);

        while ($cursor < $length) {
            $start = strpos($html, '<!--', $cursor);
            if ($start === false) {
                $this->addText(substr($html, $cursor), $stack, $blocks);
                break;
            }

            // Capture text before tag
            if ($start > $cursor) {
                $this->addText(substr($html, $cursor, $start - $cursor), $stack, $blocks);
            }

            $end = strpos($html, '-->', $start);
            if ($end === false) break;

            // Extract and trim the tag content (wp:... or /wp:...)
            $tag = trim(substr($html, $start + 4, $end - ($start + 4)));
            $cursor = $end + 3;

            // Detect block type
            $isClosing = str_starts_with($tag, '/wp:');
            $isOpening = str_starts_with($tag, 'wp:');

            if (!$isClosing && !$isOpening) {
                // Not a Gutenberg tag, treat as text
                $this->addText(substr($html, $start, $cursor - $start), $stack, $blocks);
                continue;
            }

            if ($isClosing) {
                array_pop($stack);
                continue;
            }

            // Process Opening Tag (wp:...)
            $tagContent = substr($tag, 3);
            $isVoid = substr($tagContent, -1) === '/';
            $cleanedTag = $isVoid ? rtrim($tagContent, ' /') : $tagContent;

            // Efficiently split name and attributes
            $spacePos = strpos($cleanedTag, ' ');
            if ($spacePos !== false) {
                $name  = substr($cleanedTag, 0, $spacePos);
                $attrs = json_decode(substr($cleanedTag, $spacePos + 1), true) ?: [];
            } else {
                $name  = $cleanedTag;
                $attrs = [];
            }

            // Ensure core namespace if missing
            if (strpos($name, '/') === false) {
                $name = 'core/' . $name;
            }

            $block = [
                'blockName'   => $name,
                'attrs'       => $attrs,
                'innerBlocks' => [],
                'innerHTML'   => '',
            ];

            // Attach block
            if (empty($stack)) {
                $blocks[] = &$block;
            } else {
                $stack[count($stack) - 1]['innerBlocks'][] = &$block;
            }

            if (!$isVoid) {
                $stack[] = &$block;
            }
            
            unset($block); // Clear reference for next iteration
        }

        return $blocks;
    }

    protected function addText(string $text, array &$stack, array &$root): void
    {
        if ($text === '') return;
        
        if (empty($stack)) {
            $root[] = [
                'blockName'   => null,
                'attrs'       => [],
                'innerBlocks' => [],
                'innerHTML'   => $text,
            ];
        } else {
            $stack[count($stack) - 1]['innerHTML'] .= $text;
        }
    }
}
