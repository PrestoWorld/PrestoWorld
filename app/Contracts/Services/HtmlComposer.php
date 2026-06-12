<?php

declare(strict_types=1);

namespace App\Contracts\Services;

interface HtmlComposer
{
    public function compose(string $body, string $styles, ?string $title = null): string;
}
