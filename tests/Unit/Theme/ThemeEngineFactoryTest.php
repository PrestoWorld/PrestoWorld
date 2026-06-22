<?php

declare(strict_types=1);

namespace Tests\Unit\Theme;

use PHPUnit\Framework\TestCase;
use PrestoWorld\Theme\ThemeEngineFactory;
use PrestoWorld\Contracts\Theme\ThemeEngineInterface;
use PrestoWorld\Contracts\Theme\ThemeType;
use PrestoWorld\Modules\ClassicTheme\ClassicThemeEngine;
use Witals\Framework\Contracts\Container;

class ThemeEngineFactoryTest extends TestCase
{
    private string $tmpDir;

    protected function setUp(): void
    {
        $this->tmpDir = sys_get_temp_dir() . '/presto_theme_test_' . uniqid();
        mkdir($this->tmpDir, 0777, true);
    }

    protected function tearDown(): void
    {
        $this->rmdir($this->tmpDir);
    }

    public function test_detect_classic_theme_by_default(): void
    {
        $factory = new ThemeEngineFactory($this->createMock(Container::class));
        $factory->setThemePath($this->tmpDir);

        $type = $factory->detectType();

        $this->assertSame(ThemeType::CLASSIC, $type);
    }

    public function test_detect_classic_theme_with_style_css_and_index_php(): void
    {
        file_put_contents($this->tmpDir . '/style.css', '/* Theme Name: Test */');
        file_put_contents($this->tmpDir . '/index.php', '<?php');

        $factory = new ThemeEngineFactory($this->createMock(Container::class));
        $factory->setThemePath($this->tmpDir);

        $this->assertSame(ThemeType::CLASSIC, $factory->detectType());
    }

    public function test_detect_block_theme_with_theme_json_and_templates(): void
    {
        file_put_contents($this->tmpDir . '/theme.json', '{}');
        mkdir($this->tmpDir . '/templates', 0777, true);
        file_put_contents($this->tmpDir . '/templates/index.html', '');

        $factory = new ThemeEngineFactory($this->createMock(Container::class));
        $factory->setThemePath($this->tmpDir);

        $this->assertSame(ThemeType::BLOCK, $factory->detectType());
    }

    public function test_detect_block_theme_without_index_php(): void
    {
        file_put_contents($this->tmpDir . '/theme.json', '{}');
        mkdir($this->tmpDir . '/templates', 0777, true);

        $factory = new ThemeEngineFactory($this->createMock(Container::class));
        $factory->setThemePath($this->tmpDir);

        $this->assertSame(ThemeType::BLOCK, $factory->detectType());
    }

    public function test_detect_prefers_classic_when_both_exist(): void
    {
        file_put_contents($this->tmpDir . '/style.css', '/* Theme Name: Test */');
        file_put_contents($this->tmpDir . '/index.php', '<?php');
        file_put_contents($this->tmpDir . '/theme.json', '{}');
        mkdir($this->tmpDir . '/templates', 0777, true);

        $factory = new ThemeEngineFactory($this->createMock(Container::class));
        $factory->setThemePath($this->tmpDir);

        $this->assertSame(ThemeType::CLASSIC, $factory->detectType());
    }

    public function test_create_returns_classic_engine(): void
    {
        file_put_contents($this->tmpDir . '/style.css', '/* Theme Name: Test */');
        file_put_contents($this->tmpDir . '/index.php', '<?php');

        $factory = new ThemeEngineFactory($this->createMock(Container::class));
        $factory->setThemePath($this->tmpDir);

        $engine = $factory->create();

        $this->assertInstanceOf(ClassicThemeEngine::class, $engine);
        $this->assertInstanceOf(ThemeEngineInterface::class, $engine);
    }

    public function test_create_returns_cached_classic_engine(): void
    {
        file_put_contents($this->tmpDir . '/style.css', '/* Theme Name: Test */');
        file_put_contents($this->tmpDir . '/index.php', '<?php');

        $factory = new ThemeEngineFactory($this->createMock(Container::class));
        $factory->setThemePath($this->tmpDir);

        $engine1 = $factory->create();
        $engine2 = $factory->create();

        $this->assertSame($engine1, $engine2);
    }

    public function test_create_with_explicit_path(): void
    {
        $path2 = sys_get_temp_dir() . '/presto_theme_test_' . uniqid();
        mkdir($path2, 0777, true);
        file_put_contents($path2 . '/style.css', '/* Theme Name: Test */');
        file_put_contents($path2 . '/index.php', '<?php');

        $factory = new ThemeEngineFactory($this->createMock(Container::class));
        $engine = $factory->create($path2);

        $this->assertInstanceOf(ClassicThemeEngine::class, $engine);

        $this->rmdir($path2);
    }

    public function test_create_classic_engine(): void
    {
        file_put_contents($this->tmpDir . '/style.css', '/* Theme Name: Test */');
        file_put_contents($this->tmpDir . '/index.php', '<?php');

        $factory = new ThemeEngineFactory($this->createMock(Container::class));
        $factory->setThemePath($this->tmpDir);

        $engine = $factory->createClassicEngine();
        $this->assertInstanceOf(ClassicThemeEngine::class, $engine);
    }

    public function test_create_block_engine_throws_without_gutenberg(): void
    {
        $container = $this->createMock(Container::class);
        $container->method('has')->willReturn(false);

        $factory = new ThemeEngineFactory($container);
        $factory->setThemePath($this->tmpDir);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Gutenberg module is required');

        $factory->createBlockEngine();
    }

    public function test_set_theme_path_updates_detection(): void
    {
        $factory = new ThemeEngineFactory($this->createMock(Container::class));

        $this->assertSame(ThemeType::CLASSIC, $factory->detectType());

        file_put_contents($this->tmpDir . '/theme.json', '{}');
        mkdir($this->tmpDir . '/templates', 0777, true);

        $factory->setThemePath($this->tmpDir);
        $this->assertSame(ThemeType::BLOCK, $factory->detectType());
    }

    private function rmdir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $items = array_diff(scandir($dir), ['.', '..']);
        foreach ($items as $item) {
            $path = $dir . '/' . $item;
            is_dir($path) ? $this->rmdir($path) : unlink($path);
        }

        rmdir($dir);
    }
}
