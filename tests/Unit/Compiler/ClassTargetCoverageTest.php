<?php

declare(strict_types=1);

namespace Tests\Unit\Compiler;

use PHPUnit\Framework\TestCase;

class ClassTargetCoverageTest extends TestCase
{
    /**
     * @return array<string, mixed>
     */
    private function masterMapping(): array
    {
        $path = dirname(__DIR__, 3) . '/resources/mappings/master.json';
        $decoded = json_decode((string) file_get_contents($path), true);
        $this->assertIsArray($decoded);

        return $decoded;
    }

    public function testEveryClassTargetResolves(): void
    {
        $rules = $this->masterMapping()['rules'] ?? [];
        $this->assertIsArray($rules);

        $missing = [];
        foreach ($rules as $rule) {
            if (!is_array($rule) || ($rule['kind'] ?? '') !== 'class') {
                continue;
            }

            $mode = is_string($rule['mode'] ?? null) ? $rule['mode'] : '';
            $target = is_string($rule['target'] ?? null) ? $rule['target'] : '';
            if ($target === '' || in_array($mode, ['n', 'x'], true)) {
                continue;
            }

            if (!class_exists($target) && !interface_exists($target) && !trait_exists($target)) {
                $source = is_string($rule['source'] ?? null) ? $rule['source'] : '?';
                $missing[] = $source . ' => ' . $target;
            }
        }

        $this->assertSame([], $missing, "Unresolved class targets:\n" . implode("\n", $missing));
    }

    public function testNoElementorNamespace(): void
    {
        $rules = $this->masterMapping()['rules'] ?? [];
        $this->assertIsArray($rules);

        foreach ($rules as $rule) {
            if (!is_array($rule)) {
                continue;
            }
            $target = is_string($rule['target'] ?? null) ? $rule['target'] : '';
            $this->assertStringNotContainsString('Elementor', $target);
        }
    }
}
