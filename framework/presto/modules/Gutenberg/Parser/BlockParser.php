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
                // Closing Tag — just pop; block was added by reference at open time
                if (!empty($stack)) {
                    array_pop($stack);
                }
                continue;
            }

            if (str_starts_with($tagContent, 'wp:')) {
                // Opening or Void Tag
                $tagBody   = substr($tagContent, 3);
                $isVoid    = str_ends_with($tagBody, '/');
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

                // Register in correct place via reference
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
        // Ignore pure whitespace at root level
        if (trim($text) === '' && empty($stack)) return;

        if (empty($stack)) {
            $root[] = [
                'blockName'   => null,
                'attrs'       => [],
                'innerBlocks' => [],
                'innerHTML'   => $text,
            ];
            return;
        }

        $parent = &$stack[count($stack) - 1];
        // Always accumulate into innerHTML (for blocks that use it directly)
        $parent['innerHTML'] .= $text;

        // Determine if this is an outer wrapping HTML tag (like <div class="..."> or </div>)
        // vs actual content (like <p>text</p>) that should be an innerBlock.
        // Rule: a wrapping tag is a SINGLE opening or closing HTML tag with no text content.
        $trimmed = trim($text);
        $isSingleOpenTag  = (bool) preg_match('/^<(\w+)(\s[^>]*)?\s*>$/', $trimmed); // <div class="...">
        $isSingleCloseTag = (bool) preg_match('/^<\/\w+>$/', $trimmed);              // </div>

        if (!$isSingleOpenTag && !$isSingleCloseTag && $trimmed !== '') {
            // Content worth rendering as an inner block
            $parent['innerBlocks'][] = [
                'blockName'   => null,
                'attrs'       => [],
                'innerBlocks' => [],
                'innerHTML'   => $text,
            ];
        }
    }
}
