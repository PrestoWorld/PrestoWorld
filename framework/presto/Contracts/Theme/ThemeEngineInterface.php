<?php

declare(strict_types=1);

namespace PrestoWorld\Contracts\Theme;

use App\Contracts\Services\RenderedContent;

interface ThemeEngineInterface
{
    public function render(string $template, array $post = []): RenderedContent;

    public function getStyles(): string;

    public function supports(string $template): bool;
}
