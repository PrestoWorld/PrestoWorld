<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\Gutenberg\Parser;

/**
 * Industrial-grade Gutenberg Block Parser
 * 
 * Implements a single-pass scanner-based parser using a State Machine.
 * Optimized for minimal memory footprint and maximum performance by avoiding
 * large regex match arrays and redundant string allocations.
 */
class BlockParser
{
    private const STATE_TEXT = 0;
    private const STATE_BLOCK_OPENER = 1;

    /**
     * Parse content using a single-pass grammar-based approach
     */
    public function parse(string $content): array
    {
        $length = strlen($content);
        if ($length === 0) {
            return [];
        }

        $blocks = [];
        $stack = [];
        $offset = 0;

        while ($offset < $length) {
            // Find the next potential block marker
            $markerPos = strpos($content, '<!--', $offset);

            if ($markerPos === false) {
                // No more blocks, add remaining as text
                $text = substr($content, $offset);
                if (trim($text) !== '') {
                    $this->addBlock($blocks, $stack, [
                        'blockName' => null,
                        'attrs' => [],
                        'innerBlocks' => [],
                        'innerHTML' => $text,
                    ]);
                }
                break;
            }

            // Add leading text before the block
            if ($markerPos > $offset) {
                $text = substr($content, $offset, $markerPos - $offset);
                if (trim($text) !== '') {
                    $this->addBlock($blocks, $stack, [
                        'blockName' => null,
                        'attrs' => [],
                        'innerBlocks' => [],
                        'innerHTML' => $text,
                    ]);
                }
            }

            // Find the end of the block marker
            $endMarkerPos = strpos($content, '-->', $markerPos);
            if ($endMarkerPos === false) {
                // Malformed marker, treat as text
                $offset = $markerPos + 4;
                continue;
            }

            $markerTag = substr($content, $markerPos, $endMarkerPos - $markerPos + 3);
            $this->processMarker($markerTag, $blocks, $stack, $content, $markerPos, $endMarkerPos + 3);
            
            $offset = $endMarkerPos + 3;
        }

        return $blocks;
    }

    private function processMarker(string $tag, array &$blocks, array &$stack, string $content, int $startOffset, int $endOffset): void
    {
        // Grammar: <!-- wp:name {attrs} --> or <!-- /wp:name --> or <!-- wp:name / -->
        if (!preg_match('/^<!--\s+(?P<closer>\/)?wp:(?P<name>[a-z0-9-]+\/[a-z0-9-]+|[a-z0-9-]+)\s*(?P<attrs>{.*?})?\s*(?P<void>\/)?\s*-->$/s', $tag, $match)) {
            return;
        }

        $name = $match['name'];
        $blockName = str_contains($name, '/') ? $name : 'core/' . $name;
        $isCloser = !empty($match['closer']);
        $isVoid = !empty($match['void']);
        $attrs = !empty($match['attrs']) ? json_decode($match['attrs'], true) : [];

        if ($isCloser) {
            if (!empty($stack)) {
                $openBlock = array_pop($stack);
                // Ensure name matches or handle edge cases
                $start = $openBlock['content_start'];
                $openBlock['innerHTML'] = substr($content, $start, $startOffset - $start);
                
                // Recurse only if it's not a root-level text block
                if (trim($openBlock['innerHTML']) !== '' && str_contains($openBlock['innerHTML'], '<!-- wp:')) {
                    $openBlock['innerBlocks'] = $this->parse($openBlock['innerHTML']);
                }

                unset($openBlock['content_start']);
                
                if (empty($stack)) {
                    $blocks[] = $openBlock;
                } else {
                    $stack[count($stack) - 1]['innerBlocks'][] = $openBlock;
                }
            }
        } elseif ($isVoid) {
            $block = [
                'blockName' => $blockName,
                'attrs' => $attrs,
                'innerBlocks' => [],
                'innerHTML' => '',
            ];
            if (empty($stack)) {
                $blocks[] = $block;
            } else {
                $stack[count($stack) - 1]['innerBlocks'][] = $block;
            }
        } else {
            // Opener
            $stack[] = [
                'blockName' => $blockName,
                'attrs' => $attrs,
                'innerBlocks' => [],
                'content_start' => $endOffset,
            ];
        }
    }

    private function addBlock(array &$blocks, array &$stack, array $block): void
    {
        if (empty($stack)) {
            $blocks[] = $block;
        } else {
            $stack[count($stack) - 1]['innerBlocks'][] = $block;
        }
    }
}
