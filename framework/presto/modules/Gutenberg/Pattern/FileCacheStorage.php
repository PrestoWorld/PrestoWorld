<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\Gutenberg\Pattern;

/**
 * Persistent File Storage for Traditional Servers (FPM/CGI)
 */
class FileCacheStorage implements PatternStorageInterface
{
    protected string $cacheDir;

    public function __construct(string $basePath)
    {
        $this->cacheDir = $basePath . '/storages/framework/cache/patterns';
        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0775, true);
        }
    }

    public function get(string $slug): ?string
    {
        $path = $this->getPath($slug);
        return file_exists($path) ? file_get_contents($path) : null;
    }

    public function set(string $slug, string $content): void
    {
        file_put_contents($this->getPath($slug), $content);
    }

    public function has(string $slug): bool
    {
        return file_exists($this->getPath($slug));
    }

    protected function getPath(string $slug): string
    {
        return $this->cacheDir . '/' . md5($slug) . '.cache';
    }
}
