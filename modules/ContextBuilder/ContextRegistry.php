<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\ContextBuilder;

/**
 * ContextRegistry — quản lý tất cả context types.
 *
 * Mỗi context type (taxonomy, post, page, custom) được đăng ký tại đây.
 * Context Builder dùng registry này để biết context type nào tồn tại
 * và layout tương ứng.
 */
class ContextRegistry
{
    /** @var array<string, ContextType> */
    protected array $contexts = [];

    public function register(ContextType $contextType): void
    {
        $this->contexts[$contextType->getName()] = $contextType;
    }

    public function unregister(string $name): void
    {
        unset($this->contexts[$name]);
    }

    public function has(string $name): bool
    {
        return isset($this->contexts[$name]);
    }

    public function get(string $name): ?ContextType
    {
        return $this->contexts[$name] ?? null;
    }

    /**
     * @return array<string, ContextType>
     */
    public function all(): array
    {
        return $this->contexts;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function toArray(): array
    {
        $result = [];
        foreach ($this->contexts as $name => $context) {
            $result[$name] = $context->toArray();
        }
        return $result;
    }
}
