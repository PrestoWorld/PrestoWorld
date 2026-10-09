<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\ContextBuilder\Block;

use PrestoWorld\Modules\ContextBuilder\BaseBlock;

/**
 * HumanReadablePostDateBlock — renders jankx/human-readable-post-date block.
 *
 * Displays post date in human-readable format (e.g., "3 hours ago").
 */
class HumanReadablePostDateBlock extends BaseBlock
{
    protected string $name = 'jankx/human-readable-post-date';

    protected array $attributes = [
        'showIcon' => ['type' => 'boolean', 'default' => true],
    ];

    protected string $renderMode = 'ssr';

    public function render(array $attributes, string $content = ''): string
    {
        $showIcon = $attributes['showIcon'] ?? true;
        $icon = $showIcon ? '<svg class="icon-clock" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg> ' : '';

        // In real implementation, this would compute relative time from post date
        // For now, output a placeholder that Bridge.js can enhance
        return sprintf(
            '<time class="pw-human-readable-date" datetime="%s">%s%s</time>',
            htmlspecialchars($attributes['date'] ?? date('c'), ENT_QUOTES, 'UTF-8'),
            $icon,
            htmlspecialchars($attributes['text'] ?? 'Just now', ENT_QUOTES, 'UTF-8'),
        );
    }
}