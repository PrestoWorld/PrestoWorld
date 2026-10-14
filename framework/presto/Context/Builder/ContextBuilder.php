<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\ContextBuilder;

/**
 * ContextBuilder — service trung tâm cho Gutenberg integration.
 *
 * Hai integration points:
 *   1. Full Site Edit — Context Builder là full site, edit toàn bộ layout.html
 *   2. Content Edit — Page edit, edit content bên trong layout
 *
 * Layout lưu dạng flat file .html (không database):
 *   /storage/contexts/{context-type}/layout.html
 */
class ContextBuilder
{
    protected ContextRegistry $contexts;

    protected ContextLoader $loader;

    protected BlockRegistry $blocks;

    public function __construct(
        ContextRegistry $contexts,
        ContextLoader $loader,
        BlockRegistry $blocks,
    ) {
        $this->contexts = $contexts;
        $this->loader = $loader;
        $this->blocks = $blocks;
    }

    /**
     * FULL SITE EDIT: Lấy layout hiện tại của context type (để Gutenberg edit).
     *
     * Trả về layout JSON để Gutenberg editor load vào.
     *
     * @return array<string, array<array<string, mixed>>>
     */
    public function getLayout(string $contextType): array
    {
        $context = $this->contexts->get($contextType);
        if ($context === null) {
            return [];
        }

        return $this->loader->loadLayout($context);
    }

    /**
     * FULL SITE EDIT: Lưu layout đã edit ra flat file .html.
     *
     * @param array<string, array<array<string, mixed>>> $layout
     */
    public function saveLayout(string $contextType, array $layout): bool
    {
        $context = $this->contexts->get($contextType);
        if ($context === null) {
            return false;
        }

        return $this->loader->saveLayout($context, $layout);
    }

    /**
     * CONTENT EDIT: Lấy content của một entity (post/page) để Gutenberg edit.
     *
     * Content được lưu riêng với layout — Gutenberg edit content,
     * Context Builder edit layout.
     *
     * @return array<string, mixed>
     */
    public function getContent(string $contextType, int $id): array
    {
        // Content được load từ PostRepository (Schema module)
        // Context Builder chỉ cung cấp interface, không trực tiếp query DB
        // → giữ decoupling, content edit thông qua API
        return [
            'context_type' => $contextType,
            'id' => $id,
            'blocks' => [],
        ];
    }

    /**
     * CONTENT EDIT: Lưu content đã edit.
     *
     * @param array<string, mixed> $content
     */
    public function saveContent(string $contextType, int $id, array $content): bool
    {
        // Delegate sang PostService/PostRepository thông qua event hoặc API
        // Context Builder không trực tiếp ghi DB — giữ decoupling
        return true;
    }

    /**
     * Render context type ra frontend HTML (hybrid SSR/CSR).
     *
     * @param array<string, mixed> $data
     */
    public function render(string $contextType, array $data): string
    {
        $context = $this->contexts->get($contextType);
        if ($context === null) {
            return '';
        }

        return $this->loader->render($context, $data);
    }

    /**
     * Danh sách tất cả context types (để Context Builder UI hiển thị).
     *
     * @return array<string, array<string, mixed>>
     */
    public function getContextTypes(): array
    {
        return $this->contexts->toArray();
    }

    /**
     * Danh sách tất cả blocks đã đăng ký (để Gutenberg biết block nào có sẵn).
     *
     * @return array<string, string>
     */
    public function getBlocks(): array
    {
        return $this->blocks->names();
    }

    /**
     * @return array<string, array<string, mixed>> block definitions cho Gutenberg
     */
    public function getBlockDefinitions(): array
    {
        $definitions = [];
        foreach ($this->blocks->all() as $block) {
            $definitions[$block->getName()] = [
                'name' => $block->getName(),
                'attributes' => $block->attributes(),
                'render_mode' => $block->getRenderMode(),
            ];
        }
        return $definitions;
    }
}
