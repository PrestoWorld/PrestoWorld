<?php

declare(strict_types=1);

namespace App\Contracts\Services;

interface ContentRenderer
{
    public function render(string $template, array $post = []): RenderedContent;

    /**
     * Whether the active theme provides the given template.
     */
    public function supports(string $template): bool;
}
