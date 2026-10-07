<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\ClassicTheme\Console;

use Witals\Framework\Console\Command;
use PrestoWorld\Modules\ClassicTheme\TransformerRegistry;

class GenerateStubsCommand extends Command
{
    protected string $name = 'presto:generate-stubs';
    protected string $description = 'Generate wp-stubs.php from transformer definitions';

    public function handle(array $args): int
    {
        $sourceDir = dirname(__DIR__) . '/Transformers';
        $targetFile = dirname(__DIR__) . '/wp-stubs.php';

        if (!is_dir($sourceDir)) {
            $this->error("Transformers directory not found: {$sourceDir}");
            return 1;
        }

        TransformerRegistry::registerFromDirectory($sourceDir);
        $stubs = TransformerRegistry::generateStubs();

        $functionCount = count(TransformerRegistry::getRegisteredFunctions());
        $classCount = count(TransformerRegistry::getRegisteredClasses());

        file_put_contents($targetFile, $stubs);

        $this->info("Generated {$targetFile}");
        $this->line("  Functions: {$functionCount}");
        $this->line("  Classes:   {$classCount}");

        return 0;
    }
}
