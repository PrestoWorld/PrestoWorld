<?php

declare(strict_types=1);

namespace App\Contracts\Services;

use App\Exceptions\RenderException;

interface ContentRenderer
{
    /**
     * @throws RenderException
     */
    public function render(string $template): string;

    public function getStyles(): string;
}
