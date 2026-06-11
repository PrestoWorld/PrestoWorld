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
                    $finished = array_pop($stack);
                    // Attach finished block to parent or root
                    if (!empty($stack)) {
                        $stack[count($stack) - 1]['innerBlocks'][] = $finished;
                    } else {
                        $blocks[] = $finished;
                    }
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

                if (!$isVoid) {
                    $stack[] = $block;
                } else {
                    // Void block: add to parent stack or root directly
                    if (empty($stack)) {
                        $blocks[] = $block;
                    } else {
                        $stack[count($stack) - 1]['innerBlocks'][] = $block;
                    }
                }
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
            $parent['innerHTML'] .= $text;
            // Also add as a null innerBlock so renderers can iterate it
            if (trim($text) !== '') {
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
