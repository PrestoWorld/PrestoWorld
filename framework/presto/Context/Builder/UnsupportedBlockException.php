<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\ContextBuilder;

/**
 * UnsupportedBlockException — thrown when a block is not supported for compilation.
 *
 * This exception is thrown when ContextLoader encounters a block that:
 * - Is not registered in BlockRegistry
 * - Does not have a corresponding PHP renderer class
 * - Cannot be compiled to PrestoWorld architecture
 *
 * The exception message includes the block name and instructions for resolution.
 */
class UnsupportedBlockException extends \RuntimeException
{
    protected string $blockName = '';

    public function __construct(string $message, string $blockName = '', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->blockName = $blockName;
    }

    public function getBlockName(): string
    {
        return $this->blockName;
    }

    /**
     * Get a human-readable error message with resolution instructions.
     */
    public function getResolutionMessage(): string
    {
        $blockName = $this->blockName;
        if ($blockName === '') {
            return $this->getMessage();
        }

        return sprintf(
            "Block '%s' is not supported for compilation.\n\n" .
            "To resolve this:\n" .
            "1. Create a block class in modules/ContextBuilder/Block/\n" .
            "2. Extend PrestoWorld\\Modules\\ContextBuilder\\BaseBlock\n" .
            "3. Register it in modules/ContextBuilder/Module.php\n" .
            "4. Or add it to the supported blocks list in BlockRegistry",
            $blockName,
        );
    }
}