<?php

declare(strict_types=1);

namespace PrestoWorld\Core\Compiler;

use PhpParser\ErrorHandler;
use PhpParser\Node;
use PhpParser\Node\Stmt;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\Parser;
use PhpParser\ParserFactory;
use PhpParser\PrettyPrinter\Standard as StandardPrinter;
use PrestoWorld\Core\Compiler\Mapping\MappingRegistry;
use PrestoWorld\Core\Compiler\Passes\ClassPass;
use PrestoWorld\Core\Compiler\Passes\QueryPass;
use PrestoWorld\Core\Compiler\Passes\UserFunctionPass;
use PrestoWorld\Core\Compiler\Passes\WpFunctionPass;

/**
 * Compile-time pipeline §10.2.1 — WpCompiler (spec §10.2.3).
 */
final class WpCompiler
{
    private readonly Parser $parser;
    private readonly StandardPrinter $printer;

    public function __construct(
        private readonly MappingRegistry $mappings,
        private readonly SymbolAnalyzer $analyzer,
    ) {
        $this->parser = (new ParserFactory())->createForNewestSupportedVersion();
        $this->printer = new StandardPrinter();
    }

    public function compile(
        string $sourceDir,
        string $outputDir,
        bool $dryRun = false,
        string $type = 'plugin',
    ): CompileReport {
        $scanner = new PluginScanner();
        $slug = $scanner->slug($sourceDir);
        $files = $scanner->scan($sourceDir);

        $report = new CompileReport($slug, $this->mappings->version(), $dryRun);
        $report->incrementStat(CompileReport::STAT_FILES_SCANNED, count($files));

        // Phase A-1: parse + analyze toàn bộ file (stage 2 + 3).
        /** @var array<string, array<Stmt>> $asts */
        $asts = [];
        $parseErrors = new ErrorHandler\Collecting();
        $fileIds = [];
        foreach ($files as $relativeFile) {
            $code = $this->read(rtrim($sourceDir, '/') . '/' . $relativeFile);
            $ast = $this->parser->parse($code, $parseErrors);

            if ($ast === null) {
                foreach ($parseErrors->getErrors() as $error) {
                    $report->addFailure($relativeFile, $error->getRawMessage() . ' @ line ' . $error->getStartLine());
                }
                $parseErrors->clearErrors();

                continue;
            }
            foreach ($parseErrors->getErrors() as $error) {
                $report->addFailure($relativeFile, $error->getRawMessage() . ' @ line ' . $error->getStartLine());
            }
            $parseErrors->clearErrors();

            $this->analyzer->analyze($relativeFile, $ast);
            $asts[$relativeFile] = $ast;
            $fileIds[] = $relativeFile;
        }

        // Phase A-2: dựng SymbolTable sau khi đã analyze toàn bộ file.
        $context = new CompilationContext(
            mappings: $this->mappings,
            symbols: $this->analyzer->build($slug, $type),
            report: $report,
            sourceDir: $sourceDir,
        );

        $queryPass = new QueryPass($context);
        $wpFunctionPass = new WpFunctionPass($context);
        $classPass = new ClassPass($context);
        $userFunctionPass = new UserFunctionPass($context);
        /** @var list<NodeVisitor> $passes */
        $passes = [$queryPass, $wpFunctionPass, $classPass, $userFunctionPass];

        // Phase B: transform + emit từng file (stage 4 + 5).
        foreach ($fileIds as $relativeFile) {
            $context->setCurrentFile($relativeFile);
            $newAst = $this->transform($asts[$relativeFile], $passes);

            if (!$dryRun) {
                $this->emit($outputDir, $relativeFile, $newAst);
            }
        }

        if (!$dryRun) {
            $this->emitUserClasses($context, $outputDir, $userFunctionPass);
            $this->writeFile($outputDir . '/manifest.json', $report->toJson());
        }

        return $report;
    }

    /**
     * @param array<Stmt>   $ast
     * @param list<NodeVisitor> $passes
     *
     * @return array<Node>
     */
    private function transform(array $ast, array $passes): array
    {
        $traverser = new NodeTraverser();
        $traverser->addVisitor(new NameResolver());

        foreach ($passes as $pass) {
            $traverser->addVisitor($pass);
        }
        $traverser->addVisitor(new TerminationRewriteVisitor());

        return $traverser->traverse($ast);
    }

    /**
     * @param array<Node> $newAst
     */
    private function emit(string $outputDir, string $relativeFile, array $newAst): void
    {
        $code = $this->printer->prettyPrintFile($newAst);
        $this->writeFile($outputDir . '/' . $relativeFile, $code);
    }

    /**
     * Ghi các file class sinh ra từ Pass 4 (§10.6.3).
     */
    private function emitUserClasses(CompilationContext $context, string $outputDir, UserFunctionPass $pass): void
    {
        foreach ($pass->extractedClassMethods() as $relativeFile => $methods) {
            $className = $context->symbols->classNameFor($relativeFile);
            $class = new Stmt\Class_(
                $className,
                [
                    'flags' => Stmt\Class_::MODIFIER_FINAL,
                    'stmts' => $methods,
                ],
            );
            $namespace = new Stmt\Namespace_(new Node\Name($context->symbols->namespace()), [$class]);

            $code = $this->printer->prettyPrintFile([$namespace]);
            $this->writeFile($outputDir . '/' . $className . '.php', $code);
        }
    }

    private function read(string $absolutePath): string
    {
        if (!is_file($absolutePath)) {
            throw new CompilerException("File not found: {$absolutePath}");
        }

        $contents = file_get_contents($absolutePath);
        if ($contents === false) {
            throw new CompilerException("Cannot read file: {$absolutePath}");
        }

        return $contents;
    }

    private function writeFile(string $path, string $contents): void
    {
        $directory = dirname($path);
        if (!is_dir($directory) && !mkdir($directory, 0777, true) && !is_dir($directory)) {
            throw new CompilerException("Cannot create directory: {$directory}");
        }

        if (file_put_contents($path, $contents) === false) {
            throw new CompilerException("Cannot write file: {$path}");
        }
    }
}