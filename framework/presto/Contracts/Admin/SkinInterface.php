<?php

declare(strict_types=1);

namespace PrestoWorld\Contracts\Admin;

interface SkinInterface
{
    /**
     * Get the skin unique name/slug.
     */
    public function getName(): string;

    /**
     * Render the main layout wrapper.
     *
     * @param string $content The main content to wrap.
     * @param array $args Additional arguments for the layout.
     */
    public function renderLayout(string $content, array $args = []): string;

    /**
     * Render a specific UI component.
     *
     * @param string $component The component name (e.g., 'table', 'form', 'card').
     * @param array $props Properties passed to the component.
     */
    public function renderComponent(string $component, array $props = []): string;

    /**
     * Get required assets (CSS/JS) for this skin.
     *
     * @return array{css: string[], js: string[]}
     */
    public function getAssets(): array;
}
