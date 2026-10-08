<?php

declare(strict_types=1);

namespace Tests\Unit\Compiler;

use PHPUnit\Framework\TestCase;
use PrestoWorld\Core\Compiler\CompileReport;
use PrestoWorld\Core\Compiler\Mapping\MappingRegistry;
use PrestoWorld\Core\Compiler\SymbolAnalyzer;
use PrestoWorld\Core\Compiler\WpCompiler;

class WpCompilerTest extends TestCase
{
    private string $outputDir = '';

    protected function setUp(): void
    {
        $this->outputDir = sys_get_temp_dir() . '/pw-compile-test-' . bin2hex(random_bytes(4));
    }

    protected function tearDown(): void
    {
        $this->removeDir($this->outputDir);
    }

    public function testCompileProducesManifestTransformedFileAndExtractedClass(): void
    {
        $fixture = dirname(__DIR__, 3) . '/tests/Fixtures/wp-plugin';

        $registry = MappingRegistry::fromFile(
            dirname(__DIR__, 3) . '/resources/mappings/master.json',
        );

        $report = (new WpCompiler($registry, new SymbolAnalyzer()))
            ->compile($fixture, $this->outputDir, false, 'plugin');

        $this->assertSame('wp-plugin', $report->plugin());
        $this->assertSame(1, $report->stat(CompileReport::STAT_FILES_SCANNED));
        $this->assertSame(4, $report->stat(CompileReport::STAT_USER_FUNCTIONS_COMPILED));
        $this->assertSame(1, $report->stat(CompileReport::STAT_CLASSES_MAPPED));
        $this->assertSame([], $report->failedFiles());

        $this->assertFileExists($this->outputDir . '/manifest.json');
        $this->assertFileExists($this->outputDir . '/demo-widget.php');
        $this->assertFileExists($this->outputDir . '/DemoWidget.php');

        $transformed = file_get_contents($this->outputDir . '/demo-widget.php');
        $this->assertIsString($transformed);
        $this->assertStringContainsString('LegacyTerminationException', $transformed);
        $this->assertStringContainsString('PostQuery', $transformed);

        $classCode = file_get_contents($this->outputDir . '/DemoWidget.php');
        $this->assertIsString($classCode);
        $this->assertStringContainsString('namespace Plugins\\WpPlugin;', $classCode);
        $this->assertStringContainsString('public static function dwGetProducts', $classCode);
        $this->assertStringContainsString('PrestoWorld\\Core\\Database\\PrestoWpdb::instance()', $classCode);
        $this->assertStringContainsString('Escape::html', $classCode);

        // Output phải là PHP hợp lệ.
        exec(
            'php -l ' . escapeshellarg($this->outputDir . '/DemoWidget.php') . ' 2>&1',
            $lintOutput,
            $lintCode,
        );
        $this->assertSame(0, $lintCode, implode("\n", $lintOutput));
    }

    public function testAnalyzeOnlyWritesNothing(): void
    {
        $fixture = dirname(__DIR__, 3) . '/tests/Fixtures/wp-plugin';

        $registry = MappingRegistry::fromFile(
            dirname(__DIR__, 3) . '/resources/mappings/master.json',
        );

        $report = (new WpCompiler($registry, new SymbolAnalyzer()))
            ->compile($fixture, $this->outputDir, true, 'plugin');

        $this->assertTrue($report->isAnalyzeOnly());
        $this->assertFileDoesNotExist($this->outputDir . '/manifest.json');
        $this->assertGreaterThan(0, $report->score());
    }

    private function removeDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        foreach (glob($dir . '/*') ?: [] as $file) {
            is_dir($file) ? $this->removeDir($file) : unlink($file);
        }
        @rmdir($dir);
    }
}