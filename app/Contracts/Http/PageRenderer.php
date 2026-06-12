<?php

declare(strict_types=1);

namespace App\Contracts\Http;

interface PageRenderer
{
    public function addStyle(string $css): void;
    public function render(string $body, ?string $title = null): string;
}
