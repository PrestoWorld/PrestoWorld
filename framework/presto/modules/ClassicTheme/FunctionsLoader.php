<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\ClassicTheme;

class FunctionsLoader
{
    private string $themePath;

    private bool $loaded = false;

    private array $loadedFiles = [];

    public function __construct(string $themePath)
    {
        $this->themePath = rtrim($themePath, '/');
    }

    public function load(): void
    {
        if ($this->loaded) {
            return;
        }

        $this->loaded = true;
        $functionsFile = $this->themePath . '/functions.php';

        if (!file_exists($functionsFile)) {
            return;
        }

        $this->loadFile($functionsFile);
    }

    public function isLoaded(): bool
    {
        return $this->loaded;
    }

    public function reset(): void
    {
        $this->loaded = false;
        $this->loadedFiles = [];
    }

    public function requireOnce(string $path): void
    {
        $realPath = realpath($path);

        if ($realPath === false) {
            return;
        }

        if (in_array($realPath, $this->loadedFiles, true)) {
            return;
        }

        $this->loadedFiles[] = $realPath;
        $this->loadFile($realPath);
    }

    private function loadFile(string $path): void
    {
        if (!file_exists($path)) {
            return;
        }

        $themePath = $this->themePath;

        try {
            (function () use ($path, $themePath) {
                require $path;
            })();
        } catch (\Throwable $e) {
            // Theme functions.php may depend on WordPress not being available
            // (e.g., in testing or non-WP contexts).
            $this->loaded = false;
        }
    }
}
