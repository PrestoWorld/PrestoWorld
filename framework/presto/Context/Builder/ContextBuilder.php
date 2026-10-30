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

    protected \Presto\Context\Loader $loader;

    protected BlockRegistry $blocks;

    public function __construct(
        ContextRegistry $contexts,
        \Presto\Context\Loader $loader,
        BlockRegistry $blocks,
    ) {
        $this->contexts = $contexts;
        $this->loader = $loader;
        $this->blocks = $blocks;
    }

    /**
     * Seed a default layout from the theme's templates/{name}.html when no
     * layout file exists yet (never edited, and no theme default).
     *
     * e.g. bookpress_home → content/themes/timeless/templates/home.html.
     * The template's blocks are parsed and placed into the first region so the
     * Site Editor has something to open instead of showing "item doesn't exist".
     *
     * @return array<string, array<array<string, mixed>>>
     */
    protected function seedLayoutFromTemplate(ContextType $contextType): array
    {
        $name = $contextType->getName();
        if ($name === '') {
            return [];
        }

        $themeDir = $this->loader->getThemeDir();
        if ($themeDir === '') {
            return [];
        }

        // BookPress context names are prefixed (bookpress_home) while the
        // theme templates use the raw page type (home). Strip the prefix so
        // bookpress_home → templates/home.html.
        $templateName = $this->stripBookPressPrefix($name);

        $candidates = [
            $themeDir . '/templates/' . $templateName . '.html',
            $themeDir . '/templates/' . $templateName . '/layout.html',
            $themeDir . '/templates/' . $name . '.html',
            $themeDir . '/templates/' . $name . '/layout.html',
        ];

        $templatePath = null;
        foreach ($candidates as $candidate) {
            if (is_file($candidate)) {
                $templatePath = $candidate;
                break;
            }
        }

        if ($templatePath === null) {
            return [];
        }

        $parserClass = \PrestoWorld\Module\ContextBuilder\Parser\BlockParser::class;

        try {
            $blocks = $parserClass::parseFile($templatePath);
        } catch (\Throwable $e) {
            error_log('[ContextBuilder] seedLayout parse failed: ' . $e->getMessage());

            return [];
        }

        if ($blocks === []) {
            error_log('[ContextBuilder] seedLayout: parsed 0 blocks');

            return [];
        }

        $regions = $contextType->getRegions();
        $firstRegion = is_array($regions) && $regions !== [] ? (string) array_key_first($regions) : 'content';

        return [$firstRegion => $blocks];
    }

    /**
     * Strip a BookPress context-name prefix (bookpress_) so the theme
     * template lookup uses the raw page type: bookpress_home → home,
     * bookpress_catalog → catalog.
     */
    protected function stripBookPressPrefix(string $name): string
    {
        $prefix = 'bookpress_';

        return str_starts_with($name, $prefix) ? substr($name, strlen($prefix)) : $name;
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

        $layout = $this->loader->loadLayout($context);

        // No layout file yet (never edited, and no theme default) — seed one
        // from the theme's templates/{name}.html so the Site Editor has
        // something to open instead of showing "item doesn't exist".
        // e.g. bookpress_home → content/themes/timeless/templates/home.html.
        if ($layout === []) {
            $seeded = $this->seedLayoutFromTemplate($context);
            if ($seeded !== []) {
                $layout = $seeded;
            }
        }

        return $layout;
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
