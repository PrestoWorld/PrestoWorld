<?php

declare(strict_types=1);

namespace App\Contracts\Http;

use App\Contracts\Services\RenderedContent;

interface PageRenderer
{
    public function render(RenderedContent $content, ?string $title = null): string;
}
