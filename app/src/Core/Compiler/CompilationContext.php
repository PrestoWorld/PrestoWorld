<?php

declare(strict_types=1);

namespace PrestoWorld\Core\Compiler;

use PrestoWorld\Core\Compiler\Mapping\MappingRegistry;

/**
 * Context truyền cho mọi pass qua setContext() (§10.2.3).
 */
final class CompilationContext
{
    private string $currentFile = '';

    public function __construct(
        public readonly MappingRegistry $mappings,
        public readonly SymbolTable $symbols,
        public readonly CompileReport $report,
        public readonly string $sourceDir,
        public readonly string $targetDialect = 'postgresql',
    ) {
    }

    /**
     * File RELATIVE đang được transform (vd: "includes/utils.php").
     */
    public function currentFile(): string
    {
        return $this->currentFile;
    }

    public function setCurrentFile(string $file): void
    {
        $this->currentFile = $file;
    }

    public function absolutePath(string $relativeFile): string
    {
        return rtrim($this->sourceDir, '/') . '/' . ltrim($relativeFile, '/');
    }
}
