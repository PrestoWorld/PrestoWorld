<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\ClassicTheme;

use PrestoWorld\Modules\ClassicTheme\TransformerRegistry;

class FunctionsLoader
{
    private string $themePath;

    private bool $loaded = false;

    private bool $stubsLoaded = false;

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

        putenv('PW_THEME_DIR=' . $this->themePath);
        $this->loadTransformers();
        $this->loadStubs();

        $functionsFile = $this->themePath . '/functions.php';

        if (file_exists($functionsFile)) {
            $this->loadFile($functionsFile);
        }

        // Mark as loaded even if the file failed — partial loading may have
        // registered some functions/classes before the error.
        $this->loaded = true;
    }

    public function loadTransformers(): void
    {
        $transformerDir = __DIR__ . '/Transformers';

        if (!is_dir($transformerDir)) {
            return;
        }

        TransformerRegistry::registerFromDirectory($transformerDir);
        TransformerRegistry::defineClasses();
    }

    public function loadStubs(): void
    {
        if ($this->stubsLoaded) {
            return;
        }

        $this->stubsLoaded = true;
        $stubsFile = __DIR__ . '/wp-stubs.php';

        if (file_exists($stubsFile)) {
            $this->loadFile($stubsFile);
        }
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
            // in non-WP contexts. Partial loading is acceptable — some
            // functions/classes may still have been defined before the error.
        }
    }
}
