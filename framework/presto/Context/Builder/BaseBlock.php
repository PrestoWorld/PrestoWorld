<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\ContextBuilder;

/**
 * BaseBlock — base class cho mọi block.
 *
 * Mỗi block có:
 * - name: định danh (ví dụ 'presto/heading')
 * - attributes: định nghĩa attributes với default values
 * - render_mode: 'ssr' (PHP render) hoặc 'csr' (placeholder + Bridge.js fetch)
 *
 * Hybrid render rules (§4.3.3):
 * - Parent SSR → child có thể SSR hoặc CSR
 * - Parent CSR → child bắt buộc CSR
 */
abstract class BaseBlock
{
    protected string $name = '';

    /** @var array<string, mixed> */
    protected array $attributes = [];

    protected string $renderMode = 'ssr';

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return array<string, mixed>
     */
    public function attributes(): array
    {
        return $this->attributes;
    }

    public function getRenderMode(): string
    {
        return $this->renderMode;
    }

    public function isCsr(): bool
    {
        return $this->renderMode === 'csr';
    }

    /**
     * Render block ra HTML.
     *
     * @param array<string, mixed> $attributes
     */
    abstract public function render(array $attributes, string $content = ''): string;

    /**
     * Render placeholder cho CSR mode.
     * Bridge.js sẽ fetch nội dung từ /pw-api/v1/render-block.
     *
     * @param array<string, mixed> $attributes
     */
    public function renderPlaceholder(array $attributes, string $content = ''): string
    {
        $id = $this->name . '-' . uniqid();
        $settings = json_encode($attributes, JSON_THROW_ON_ERROR);

        return sprintf(
            '<div class="pw-lazy" data-id="%s" data-type="%s" data-settings=\'%s\'></div>',
            $id,
            $this->name,
            $settings
        );
    }

    /**
     * Render hybrid: tuỳ thuộc vào render_mode mà trả về SSR HTML hoặc CSR placeholder.
     *
     * @param array<string, mixed> $attributes
     */
    public function renderHybrid(array $attributes, string $content = '', ?string $parentMode = null): string
    {
        // Rule 2: Parent CSR → child must be CSR
        if ($parentMode === 'csr') {
            return $this->renderPlaceholder($attributes, $content);
        }

        if ($this->isCsr()) {
            return $this->renderPlaceholder($attributes, $content);
        }

        return $this->render($attributes, $content);
    }
}
