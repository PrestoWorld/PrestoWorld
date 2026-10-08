<?php

declare(strict_types=1);

namespace Tests\Unit\Compiler;

use PHPUnit\Framework\TestCase;
use PrestoWorld\Core\Compiler\CompileIssue;
use PrestoWorld\Core\Compiler\CompileReport;

class CompileReportTest extends TestCase
{
    public function testScoreDeductsPerGroupSupportedUnsupported(): void
    {
        $report = new CompileReport('demo', '1.0.0', false);
        $report->addIssue(new CompileIssue(
            type: CompileIssue::UNSUPPORTED_FUNCTION,
            symbol: 'mysqli_query',
            file: 'index.php',
            line: 4,
            group: 'mysqli',
        ));
        $report->addIssue(new CompileIssue(
            type: CompileIssue::UNSUPPORTED_FUNCTION,
            symbol: 'mysqli_connect',
            file: 'index.php',
            line: 5,
            group: 'mysqli',
        ));
        $report->addIssue(new CompileIssue(
            type: CompileIssue::QUERY_UNPARSED,
            symbol: 'wpdb->query',
            file: 'index.php',
            line: 9,
        ));
        $report->addIssue(new CompileIssue(
            type: CompileIssue::STRUCTURAL_CLASS,
            symbol: 'WP_Query',
            file: 'index.php',
            line: 12,
        ));
        $report->incrementStat(CompileReport::STAT_USER_FUNCTIONS_LEFTOVER, 2);
        $report->incrementStat(CompileReport::STAT_USER_FUNCTIONS_SKIPPED, 3);

        $this->assertSame(
            100 - 20 - 30 - 10 - 3 * 2 - 3,
            $report->score(),
        );
        $this->assertGreaterThanOrEqual(0, $report->score());
    }

    public function testScoreIsNeverNegative(): void
    {
        $report = new CompileReport('demo', '1.0.0', true);
        for ($i = 0; $i < 10; $i++) {
            $report->addIssue(new CompileIssue(
                type: CompileIssue::UNSUPPORTED_FUNCTION,
                symbol: 'fn_' . $i,
                file: 'index.php',
                group: 'group_' . $i,
            ));
        }

        $this->assertSame(0, $report->score());
    }

    public function testAnalyzeOnlyDeductsFive(): void
    {
        $report = new CompileReport('demo', '1.0.0', true);

        $this->assertSame(95, $report->score());
        $this->assertTrue($report->isAnalyzeOnly());
    }

    public function testToArrayContainsReportSections(): void
    {
        $report = new CompileReport('demo', '1.0.0', true);
        $report->addIssue(new CompileIssue(
            type: CompileIssue::UNSUPPORTED_FUNCTION,
            symbol: 'wp_remote_post',
            file: 'index.php',
            reason: 'unknown',
            fallback: 'shim',
        ));
        $report->addFailure('broken.php', 'parse error @ line 1');

        $data = $report->toArray();
        $issues = $data['issues'];
        $this->assertIsArray($issues);
        /** @var list<array<string, mixed>> $issues */
        $issue = $issues[0] ?? null;

        $this->assertSame('demo', $data['plugin']);
        $this->assertSame('1.0.0', $data['mapping_version']);
        $this->assertIsArray($data['stats']);
        $this->assertArrayHasKey('failed_files', $data);
        $this->assertSame(['wp_remote_post'], $data['fallback_shims']);
        $this->assertNotNull($issue);
        $this->assertSame('unsupported_function', $issue['type']);
    }

    public function testUserFunctionMapRoundTrips(): void
    {
        $report = new CompileReport('demo', '1.0.0', false);
        $report->mapUserFunction('dw_get_products', 'Plugins\\Demo\\Fns::dwGetProducts');

        $this->assertSame(
            ['dw_get_products' => 'Plugins\\Demo\\Fns::dwGetProducts'],
            $report->userFunctionMap(),
        );
    }
}