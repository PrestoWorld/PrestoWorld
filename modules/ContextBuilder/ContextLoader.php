<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\ContextBuilder;

use PrestoWorld\Modules\ContextBuilder\Parser\BlockParser;
use PrestoWorld\Modules\Gutenberg\Renderer\BlockRenderer;

/**
 * ContextLoader — render layout cho context type ra frontend.
 *
 * FILE STORAGE — chỉ chấp nhận 2 loại file:
 *   1. Default template → nằm trong THEME:
 *      /themes/{theme}/templates/{context-type}/template-canvas.php
 *      /themes/{theme}/templates/{context-type}/render.php
 *   2. File đã chỉnh sửa → nằm trong STORAGE:
 *      /storage/contexts/{context-type}/layout.html
 *
 * Resolve order: STORAGE (user-edited) → THEME (default) → fallback.
 *
 * render.php là con của template-canvas.php:
 *   template-canvas.php (parent canvas — HTML shell, regions)
 *       └── render.php (child — render layout cho context type)
 *
 * Hybrid render: tuỳ thuộc vào settings.render_mode của từng block
 * mà render SSR (PHP) hoặc CSR (placeholder + Bridge.js fetch).
 *
 * THROW EXCEPTION nếu có thành phần chưa hỗ trợ compile.
 */
class ContextLoader
{
    protected BlockRegistry $blocks;

    protected string $storageDir;

    protected string $themeDir;

    protected ?BlockRenderer $blockRenderer = null;

    /** @var array<string, bool> */
    protected array $unsupportedBlocks = [];

    public function __construct(BlockRegistry $blocks, string $storageDir, string $themeDir = '')
    {
        $this->blocks = $blocks;
        $this->storageDir = $storageDir;
        $this->themeDir = $themeDir;
    }

    /**
     * Set the BlockRenderer for rendering parsed blocks.
     */
    public function setBlockRenderer(BlockRenderer $blockRenderer): void
    {
        $this->blockRenderer = $blockRenderer;
    }

    /**
     * Render toàn bộ context type ra HTML.
     *
     * Layout được load từ flat file .html trong STORAGE (hoặc THEME default).
     *
     * @param ContextType $contextType
     * @param array<string, mixed> $data dữ liệu context (post, terms, ...)
     * @return string HTML hoàn chỉnh
     * @throws UnsupportedBlockException nếu có block chưa hỗ trợ
     */
    public function render(ContextType $contextType, array $data): string
    {
        $layout = $this->loadLayout($contextType);
        $regions = $this->renderRegions($contextType, $layout, $data);

        return $this->renderCanvas($contextType, $data, $regions);
    }

    /**
     * Render template file (HTML with block comments).
     *
     * @param string $templatePath
     * @param array<string, mixed> $data
     * @return string
     * @throws UnsupportedBlockException
     */
    public function renderTemplate(string $templatePath, array $data = []): string
    {
        $resolvedPath = $this->resolveTemplatePath($templatePath);
        if ($resolvedPath === null) {
            throw new \RuntimeException("Template not found: $templatePath");
        }

        $blocks = BlockParser::parseFile($resolvedPath);

        if ($this->blockRenderer !== null) {
            if ($data !== []) {
                $this->blockRenderer->mergeContext($data);
            }

            return $this->blockRenderer->render($blocks);
        }

        // Fallback: render blocks manually
        return $this->renderParsedBlocks($blocks, $data);
    }

    /**
     * Whether the theme (or storage) provides the given template.
     */
    public function supports(string $template): bool
    {
        return $this->resolveTemplatePath($template) !== null;
    }

    /**
     * Render parsed blocks manually (when BlockRenderer is not available).
     *
     * @param array<int, array<string, mixed>> $blocks
     * @param array<string, mixed> $data
     * @return string
     * @throws UnsupportedBlockException
     */
    protected function renderParsedBlocks(array $blocks, array $data): string
    {
        $html = '';
        foreach ($blocks as $block) {
            $html .= $this->renderParsedBlock($block, $data);
        }
        return $html;
    }

    /**
     * @param array<string, mixed> $block
     * @param array<string, mixed> $data
     * @return string
     * @throws UnsupportedBlockException
     */
    protected function renderParsedBlock(array $block, array $data): string
    {
        $blockName = $block['blockName'] ?? null;

        // Text block
        if ($blockName === null) {
            return $block['innerHTML'] ?? '';
        }

        // Check if block is supported
        $blockInstance = $this->blocks->get($blockName);
        if ($blockInstance === null) {
            // Throw exception for unsupported blocks
            throw new UnsupportedBlockException(
                "Block '$blockName' is not supported for compilation. " .
                "Register it in BlockRegistry or add to supported blocks list."
            );
        }

        // Render inner blocks first
        $innerHtml = '';
        foreach ($block['innerBlocks'] as $innerBlock) {
            $innerHtml .= $this->renderParsedBlock($innerBlock, $data);
        }

        // Render this block
        $attributes = $block['attrs'] ?? [];
        return $blockInstance->renderHybrid($attributes, $innerHtml);
    }

    /**
     * Load layout từ flat file .html.
     *
     * Resolve order:
     *   1. STORAGE: /storage/contexts/{context-type}/layout.html (user đã edit)
     *   2. THEME:   /themes/{theme}/templates/{context-type}/layout.html (default)
     *
     * @return array<string, array<array<string, mixed>>>
     */
    public function loadLayout(ContextType $contextType): array
    {
        $path = $this->resolveLayoutPath($contextType);

        if ($path === null || !is_file($path)) {
            return [];
        }

        $json = file_get_contents($path);
        if ($json === false) {
            return [];
        }

        $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

        return $decoded['regions'] ?? $decoded;
    }

    /**
     * Lưu layout đã chỉnh sửa ra flat file .html trong STORAGE.
     *
     * @param array<string, array<array<string, mixed>>> $layout
     */
    public function saveLayout(ContextType $contextType, array $layout): bool
    {
        $path = $this->getStorageLayoutPath($contextType);
        if ($path === null) {
            return false;
        }

        $dir = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $payload = json_encode(
            ['regions' => $layout],
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR,
        );

        return file_put_contents($path, $payload) !== false;
    }

    /**
     * Resolve layout path: STORAGE (edited) → THEME (default).
     */
    protected function resolveLayoutPath(ContextType $contextType): ?string
    {
        // 1. STORAGE — user đã chỉnh sửa
        $storagePath = $this->getStorageLayoutPath($contextType);
        if ($storagePath !== null && is_file($storagePath)) {
            return $storagePath;
        }

        // 2. THEME — default template
        $themePath = $this->getThemeLayoutPath($contextType);
        if ($themePath !== null && is_file($themePath)) {
            return $themePath;
        }

        return null;
    }

    protected function getStorageLayoutPath(ContextType $contextType): ?string
    {
        $name = $contextType->getName();
        if ($name === '') {
            return null;
        }

        return $this->storageDir . '/' . $name . '/layout.html';
    }

    protected function getThemeLayoutPath(ContextType $contextType): ?string
    {
        $name = $contextType->getName();
        if ($name === '' || $this->themeDir === '') {
            return null;
        }

        return $this->themeDir . '/templates/' . $name . '/layout.html';
    }

    /**
     * Render template-canvas.php (parent) với các regions đã được render.
     *
     * Canvas resolve order: STORAGE → THEME → fallback.
     *
     * @param array<string, string> $regions
     */
    protected function renderCanvas(ContextType $contextType, array $data, array $regions): string
    {
        $canvasPath = $this->resolveCanvasPath($contextType);

        if ($canvasPath === null) {
            return $this->renderFallbackCanvas($contextType, $data, $regions);
        }

        ob_start();
        /** @psalm-suppress MixedArgument */
        include $canvasPath;
        return (string) ob_get_clean();
    }

    /**
     * Resolve canvas path: STORAGE → THEME → fallback.
     */
    protected function resolveCanvasPath(ContextType $contextType): ?string
    {
        $name = $contextType->getName();
        if ($name === '') {
            return null;
        }

        // 1. STORAGE — user đã chỉnh sửa canvas
        $storageCanvas = $this->storageDir . '/' . $name . '/template-canvas.php';
        if (is_file($storageCanvas)) {
            return $storageCanvas;
        }

        // 2. THEME — default canvas
        if ($this->themeDir !== '') {
            $themeCanvas = $this->themeDir . '/templates/' . $name . '/template-canvas.php';
            if (is_file($themeCanvas)) {
                return $themeCanvas;
            }
            // Fallback: theme root canvas
            $themeRootCanvas = $this->themeDir . '/templates/template-canvas.php';
            if (is_file($themeRootCanvas)) {
                return $themeRootCanvas;
            }
        }

        return null;
    }

    /**
     * Render tất cả regions từ layout.
     *
     * @param array<string, array<array<string, mixed>>> $layout
     * @param array<string, mixed> $data
     * @return array<string, string>
     * @throws UnsupportedBlockException
     */
    protected function renderRegions(ContextType $contextType, array $layout, array $data): array
    {
        $regions = [];
        $parentMode = $contextType->getRenderMode();

        foreach ($contextType->getRegions() as $regionName => $regionConfig) {
            $regionBlocks = $layout[$regionName] ?? [];
            $regions[$regionName] = $this->renderRegion(
                $regionName,
                $regionBlocks,
                $data,
                $parentMode,
            );
        }

        return $regions;
    }

    /**
     * Render một region (ví dụ 'content', 'sidebar') — danh sách blocks.
     *
     * @param array<array<string, mixed>> $regionBlocks
     * @param array<string, mixed> $data
     * @throws UnsupportedBlockException
     */
    protected function renderRegion(string $regionName, array $regionBlocks, array $data, string $parentMode): string
    {
        $html = '';

        foreach ($regionBlocks as $blockConfig) {
            $blockName = $blockConfig['type'] ?? '';
            $attributes = $blockConfig['attributes'] ?? [];
            $innerContent = $blockConfig['content'] ?? '';
            $innerBlocks = $blockConfig['blocks'] ?? [];

            $block = $this->blocks->get($blockName);
            if ($block === null) {
                // Throw exception for unsupported blocks
                throw new UnsupportedBlockException(
                    "Block '$blockName' in region '$regionName' is not supported for compilation."
                );
            }

            // Hybrid render: SSR hoặc CSR tuỳ thuộc vào block settings
            $html .= $block->renderHybrid($attributes, $innerContent, $parentMode);

            // Inner blocks: nếu parent là CSR thì inner cũng CSR
            $effectiveMode = $parentMode === 'csr' ? 'csr' : $block->getRenderMode();
            foreach ($innerBlocks as $innerBlockConfig) {
                $innerBlock = $this->blocks->get($innerBlockConfig['type'] ?? '');
                if ($innerBlock === null) {
                    $innerTypeName = $innerBlockConfig['type'] ?? '';
                    throw new UnsupportedBlockException(
                        "Inner block '{$innerTypeName}' is not supported for compilation."
                    );
                }
                $html .= $innerBlock->renderHybrid(
                    $innerBlockConfig['attributes'] ?? [],
                    $innerBlockConfig['content'] ?? '',
                    $effectiveMode,
                );
            }
        }

        return $html;
    }

    /**
     * Resolve template path: STORAGE → THEME.
     */
    protected function resolveTemplatePath(string $templatePath): ?string
    {
        $candidates = [];

        // 1. STORAGE
        $candidates[] = $this->storageDir . '/' . $templatePath;

        // 2. THEME
        if ($this->themeDir !== '') {
            $candidates[] = $this->themeDir . '/templates/' . $templatePath;
            $candidates[] = $this->themeDir . '/' . $templatePath;
        }

        foreach ($candidates as $path) {
            if (is_file($path)) {
                return $path;
            }

            if (is_file($path . '.html')) {
                return $path . '.html';
            }
        }

        return null;
    }

    /**
     * @param array<string, string> $regions
     */
    protected function renderFallbackCanvas(ContextType $contextType, array $data, array $regions): string
    {
        $label = htmlspecialchars($contextType->getLabel(), ENT_QUOTES, 'UTF-8');
        $regionHtml = implode("\n", $regions);

        return sprintf(
            "<!DOCTYPE html>\n<html>\n<head><title>%s</title></head>\n<body>\n%s\n</body>\n</html>",
            $label,
            $regionHtml,
        );
    }

    /**
     * Get list of unsupported blocks encountered during rendering.
     *
     * @return array<string, bool>
     */
    public function getUnsupportedBlocks(): array
    {
        return $this->unsupportedBlocks;
    }
}