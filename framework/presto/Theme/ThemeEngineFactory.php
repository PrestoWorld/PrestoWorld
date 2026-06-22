<?php

declare(strict_types=1);

namespace PrestoWorld\Theme;

use PrestoWorld\Contracts\Theme\ThemeEngineInterface;
use PrestoWorld\Contracts\Theme\ThemeType;
use PrestoWorld\Modules\ClassicTheme\ClassicThemeEngine;
use PrestoWorld\Modules\Gutenberg\Module as GutenbergModule;
use Witals\Framework\Contracts\Container;

class ThemeEngineFactory
{
    private ?ClassicThemeEngine $classicEngine = null;

    private ?BlockThemeEngineAdapter $blockEngine = null;

    private ?string $themePath = '';

    public function __construct(
        private Container $container,
    ) {}

    public function setThemePath(string $path): void
    {
        $this->themePath = $path;
    }

    public function detectType(?string $path = null): ThemeType
    {
        $path ??= $this->themePath;

        if ($path === null || $path === '') {
            return ThemeType::CLASSIC;
        }

        $hasThemeJson = file_exists($path . '/theme.json');
        $hasTemplatesDir = is_dir($path . '/templates');
        $hasIndexPhp = file_exists($path . '/index.php');

        if ($hasThemeJson && $hasTemplatesDir && !$hasIndexPhp) {
            return ThemeType::BLOCK;
        }

        return ThemeType::CLASSIC;
    }

    public function create(?string $path = null): ThemeEngineInterface
    {
        $path ??= $this->themePath;
        $type = $this->detectType($path);

        return match ($type) {
            ThemeType::CLASSIC => $this->createClassicEngine($path),
            ThemeType::BLOCK => $this->createBlockEngine(),
        };
    }

    public function createClassicEngine(?string $path = null): ClassicThemeEngine
    {
        if ($this->classicEngine === null) {
            $this->classicEngine = new ClassicThemeEngine(
                $path ?? $this->themePath,
                $this->container,
            );
        }

        return $this->classicEngine;
    }

    public function createBlockEngine(): BlockThemeEngineAdapter
    {
        if ($this->blockEngine === null) {
            $gutenberg = $this->container->has(GutenbergModule::class)
                ? $this->container->make(GutenbergModule::class)
                : null;

            if ($gutenberg === null) {
                throw new \RuntimeException(
                    'Gutenberg module is required for block theme rendering but is not registered.'
                );
            }

            $this->blockEngine = new BlockThemeEngineAdapter($gutenberg);
        }

        return $this->blockEngine;
    }
}
