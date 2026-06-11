<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\Gutenberg\Parser;

/**
 * Clean & Ultra-High-Performance Gutenberg Parser
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

            // Capture text/content between tags
            if ($start > $cursor) {
                $this->addText(substr($html, $cursor, $start - $cursor), $stack, $blocks);
            }

            $end = strpos($html, '-->', $start);
            if ($end === false) {
                $this->addText(substr($html, $start), $stack, $blocks);
                break;
            }

            $tagContent = trim(substr($html, $start + 4, $end - ($start + 4)));
            $cursor = $end + 3;

            if (str_starts_with($tagContent, '/wp:')) {
                // Closing Tag
                if (!empty($stack)) {
                    $parent = array_pop($stack);
                    // Standardize: if innerBlocks is empty, use the captured text as innerHTML
                    // This is handled during addText but ensured here.
                }
                continue;
            }

            if (str_starts_with($tagContent, 'wp:')) {
                // Opening or Void Tag
                $tagBody = substr($tagContent, 3);
                $isVoid  = str_ends_with($tagBody, '/');
                $cleanBody = $isVoid ? rtrim($tagBody, ' /') : $tagBody;

                $spacePos = strpos($cleanBody, ' ');
                if ($spacePos !== false) {
                    $name  = substr($cleanBody, 0, $spacePos);
                    $attrs = json_decode(substr($cleanBody, $spacePos + 1), true) ?: [];
                } else {
                    $name  = $cleanBody;
                    $attrs = [];
                }

                if (strpos($name, '/') === false) $name = 'core/' . $name;

                $block = [
                    'blockName'   => $name,
                    'attrs'       => $attrs,
                    'innerBlocks' => [],
                    'innerHTML'   => '',
                ];

                if (empty($stack)) {
                    $blocks[] = &$block;
                } else {
                    $stack[count($stack) - 1]['innerBlocks'][] = &$block;
                }

                if (!$isVoid) {
                    $stack[] = &$block;
                }
                unset($block);
                continue;
            }

            // Not a Gutenberg tag, treat as text
            $this->addText(substr($html, $start, $cursor - $start), $stack, $blocks);
        }

        return $blocks;
    }

    protected function addText(string $text, array &$stack, array &$root): void
    {
        // PERFORMANCE: Ignore whitespace fragments between blocks at root level
        $isWhitespace = trim($text) === '';
        if ($isWhitespace && empty($stack)) return;

        if (empty($stack)) {
            $root[] = [
                'blockName'   => null,
                'attrs'       => [],
                'innerBlocks' => [],
                'innerHTML'   => $text,
            ];
        } else {
            $parent = &$stack[count($stack) - 1];
            
            // CLEANUP: If this text looks like an opening/closing HTML tag of the PARENT block,
            // we store it in innerHTML but DONT add it to innerBlocks.
            // This prevents double-tagging when the renderer wraps the block.
            $isWrappingTag = preg_match('/^<\/?([a-z0-9]+)[^>]*>$/i', trim($text));
            
            if ($isWrappingTag) {
                $parent['innerHTML'] .= $text;
            } else {
                $parent['innerBlocks'][] = [
                    'blockName'   => null,
                    'attrs'       => [],
                    'innerBlocks' => [],
                    'innerHTML'   => $text,
                ];
            }
        }
    }
}
