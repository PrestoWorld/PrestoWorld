<?php

declare(strict_types=1);

namespace App\Http;

use Witals\Framework\Http\Request;

class TemplateResolver
{
    public function resolve(Request $request): string
    {
        $path = rtrim($request->path(), '/');

        return match (true) {
            $path === '' || $path === '/' => 'index',
            str_starts_with($path, '/search') => 'search',
            default => 'index',
        };
    }
}
