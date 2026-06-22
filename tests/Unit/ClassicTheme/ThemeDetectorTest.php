<?php

declare(strict_types=1);

namespace Tests\Unit\ClassicTheme;

use PHPUnit\Framework\TestCase;
use PrestoWorld\Modules\ClassicTheme\ThemeDetector;
use PrestoWorld\Contracts\Theme\ThemeType;

class ThemeDetectorTest extends TestCase
{
    private string $tmpDir;

    protected function setUp(): void
    {
        $this->tmpDir = sys_get_temp_dir() . '/presto_detector_' . uniqid();
        mkdir($this->tmpDir, 0777, true);
    }

    protected function tearDown(): void
    {
        array_map('unlink', glob($this->tmpDir . '/*'));
        array_map('rmdir', glob($this->tmpDir . '/*', GLOB_ONLYDIR));
        rmdir($this->tmpDir);
    }

    public function test_detect_classic_with_style_and_index(): void
    {
        file_put_contents($this->tmpDir . '/style.css', '/* Theme */');
        file_put_contents($this->tmpDir . '/index.php', '<?php');

        $this->assertSame(ThemeType::CLASSIC, ThemeDetector::detect($this->tmpDir));
    }

    public function test_detect_block_with_theme_json_and_templates(): void
    {
        file_put_contents($this->tmpDir . '/theme.json', '{}');
        mkdir($this->tmpDir . '/templates');
        file_put_contents($this->tmpDir . '/templates/index.html', '');

        $this->assertSame(ThemeType::BLOCK, ThemeDetector::detect($this->tmpDir));
    }

    public function test_detect_block_when_no_style_or_index(): void
    {
        $this->assertSame(ThemeType::BLOCK, ThemeDetector::detect($this->tmpDir));
    }

    public function test_detect_block_when_only_theme_json(): void
    {
        file_put_contents($this->tmpDir . '/theme.json', '{}');

        $this->assertSame(ThemeType::BLOCK, ThemeDetector::detect($this->tmpDir));
    }

    public function test_detect_classic_with_minimal_files(): void
    {
        file_put_contents($this->tmpDir . '/index.php', '<?php');

        $this->assertSame(ThemeType::CLASSIC, ThemeDetector::detect($this->tmpDir));
    }

    public function test_is_classic(): void
    {
        file_put_contents($this->tmpDir . '/style.css', '/* Theme */');
        file_put_contents($this->tmpDir . '/index.php', '<?php');

        $this->assertTrue(ThemeDetector::isClassic($this->tmpDir));
        $this->assertFalse(ThemeDetector::isBlock($this->tmpDir));
    }

    public function test_is_block(): void
    {
        file_put_contents($this->tmpDir . '/theme.json', '{}');
        mkdir($this->tmpDir . '/templates');

        $this->assertTrue(ThemeDetector::isBlock($this->tmpDir));
        $this->assertFalse(ThemeDetector::isClassic($this->tmpDir));
    }

    public function test_resolve_active_theme_path_prefers_public(): void
    {
        file_put_contents($this->tmpDir . '/style.css', '/* Theme */');
        file_put_contents($this->tmpDir . '/index.php', '<?php');

        $result = ThemeDetector::resolveActiveThemePath(dirname($this->tmpDir), basename($this->tmpDir));

        $this->assertStringContainsString('public/wp-content/themes/' . basename($this->tmpDir), $result);
    }

    public function test_resolve_active_theme_path_falls_back(): void
    {
        $result = ThemeDetector::resolveActiveThemePath('/tmp', 'nonexistent-theme');

        $this->assertSame('/tmp/public/wp-content/themes/nonexistent-theme', $result);
    }
}
