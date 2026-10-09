<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\ContextBuilder\Block;

use PrestoWorld\Modules\ContextBuilder\BaseBlock;

/**
 * QueryLoopBlock — block query loop, render CSR theo mặc định.
 *
 * Query loop thường nặng (nhiều posts, pagination) → CSR để không block SSR.
 * Bridge.js sẽ fetch dữ liệu từ /pw-api/v1/query-loop.
 */
class QueryLoopBlock extends BaseBlock
{
    protected string $name = 'presto/query-loop';

    protected array $attributes = [
        'postType' => ['type' => 'string', 'default' => 'post'],
        'perPage' => ['type' => 'integer', 'default' => 10],
        'orderBy' => ['type' => 'string', 'default' => 'date'],
        'order' => ['type' => 'string', 'default' => 'desc'],
        'categories' => ['type' => 'array', 'default' => []],
    ];

    protected string $renderMode = 'csr';

    public function render(array $attributes, string $content = ''): string
    {
        // QueryLoop mặc định là CSR, nhưng vẫn có render() cho trường hợp
        // cần SSR (ví dụ: SEO-critical first page).
        $postType = htmlspecialchars($attributes['postType'] ?? 'post', ENT_QUOTES, 'UTF-8');
        $perPage = (int) ($attributes['perPage'] ?? 10);

        return sprintf(
            '<div class="pw-query-loop" data-post-type="%s" data-per-page="%d"></div>',
            $postType,
            $perPage,
        );
    }
}
