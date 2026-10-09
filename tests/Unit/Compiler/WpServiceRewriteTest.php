<?php

declare(strict_types=1);

namespace Tests\Unit\Compiler;

use PHPUnit\Framework\TestCase;
use PrestoWorld\Core\Compiler\Mapping\MappingRegistry;
use PrestoWorld\Core\Compiler\SymbolAnalyzer;
use PrestoWorld\Core\Compiler\WpCompiler;

/**
 * Kiểm chứng compiler dịch hàm WP (Groups 5/14/15/16/17) sang static method
 * của service tương ứng theo resources/mappings/master.json — tức không cần
 * runtime shim cho các hàm này.
 */
class WpServiceRewriteTest extends TestCase
{
    private string $outputDir = '';

    protected function setUp(): void
    {
        $this->outputDir = sys_get_temp_dir() . '/pw-service-rewrite-' . bin2hex(random_bytes(4));
    }

    protected function tearDown(): void
    {
        $this->removeDir($this->outputDir);
    }

    public function testCompileRewritesFunctionsToServiceStaticCalls(): void
    {
        $root = dirname(__DIR__, 3);
        $fixture = $root . '/tests/Fixtures/wp-group-services-plugin';

        $registry = MappingRegistry::fromFile($root . '/resources/mappings/master.json');

        $report = (new WpCompiler($registry, new SymbolAnalyzer()))
            ->compile($fixture, $this->outputDir, false, 'plugin');

        $this->assertSame([], $report->failedFiles());

        $code = '';
        foreach (glob($this->outputDir . '/*.php') ?: [] as $file) {
            $code .= (string) file_get_contents($file);
        }

        $expected = [
            'PostLoop::thePost',
            'PostView::getTitle',
            'MetaRepository::get',
            'TermRepository::query',
            'CommentRepository::query',
            'PostService::create',
            'UploadService::bits',
            'Scheduler::once',
            'RestController::ensureResponse',
            'MediaService::subsizes',
        ];

        foreach ($expected as $needle) {
            $this->assertStringContainsString($needle, $code, "Expected rewrite to {$needle}");
        }

        foreach (glob($this->outputDir . '/*.php') ?: [] as $file) {
            exec('php -l ' . escapeshellarg($file) . ' 2>&1', $lintOutput, $lintCode);
            $this->assertSame(0, $lintCode, implode("\n", $lintOutput));
        }
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
