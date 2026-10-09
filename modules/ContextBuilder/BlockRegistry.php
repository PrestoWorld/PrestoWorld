<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\ContextBuilder;

/**
 * BlockRegistry — đăng ký và quản lý các block.
 *
 * Blocks được đăng ký bởi Module::boot() hoặc auto-discover từ plugins.
 * Mỗi block là một instance của BaseBlock.
 */
class BlockRegistry
{
    /** @var array<string, BaseBlock> */
    protected array $blocks = [];

    public function register(BaseBlock $block): void
    {
        $this->blocks[$block->getName()] = $block;
    }

    public function unregister(string $name): void
    {
        unset($this->blocks[$name]);
    }

    public function has(string $name): bool
    {
        return isset($this->blocks[$name]);
    }

    public function get(string $name): ?BaseBlock
    {
        return $this->blocks[$name] ?? null;
    }

    /**
     * @return array<string, BaseBlock>
     */
    public function all(): array
    {
        return $this->blocks;
    }

    /**
     * @return array<string, string> danh sách name => label
     */
    public function names(): array
    {
        return array_map(
            fn (BaseBlock $b) => $b->getName(),
            $this->blocks,
        );
    }
}
