<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\ContextBuilder;

/**
 * ContextType — định nghĩa một loại data (taxonomy, post, page, custom).
 *
 * Mỗi context type có:
 * - name: định danh duy nhất (ví dụ 'post', 'page', 'category')
 * - label: tên hiển thị
 * - renderMode: mặc định 'ssr' hoặc 'csr' cho toàn bộ context
 * - regions: danh sách layout regions (header, content, sidebar, footer)
 * - template: đường dẫn tới render.php (child của template-canvas.php)
 */
class ContextType
{
    /** @var array<string, mixed> */
    protected array $regions = [];

    /**
     * @param array<string, mixed> $config
     */
    public function __construct(
        protected string $name,
        protected string $label,
        protected array $config = [],
    ) {
        $this->regions = $config['regions'] ?? [];
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    /**
     * Render mode mặc định cho context type này.
     * Các block riêng lẻ có thể override bằng settings.render_mode.
     */
    public function getRenderMode(): string
    {
        return $this->config['render_mode'] ?? 'ssr';
    }

    /**
     * @return array<string, mixed> danh sách regions: ['content' => [...], 'sidebar' => [...]]
     */
    public function getRegions(): array
    {
        return $this->regions;
    }

    public function hasRegion(string $region): bool
    {
        return isset($this->regions[$region]);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getRegion(string $region): ?array
    {
        return $this->regions[$region] ?? null;
    }

    /**
     * Template child (render.php) — kế thừa template-canvas.php.
     */
    public function getTemplate(): string
    {
        return $this->config['template'] ?? 'render.php';
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'label' => $this->label,
            'render_mode' => $this->getRenderMode(),
            'regions' => $this->regions,
            'template' => $this->getTemplate(),
        ];
    }
}
