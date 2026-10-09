<?php

declare(strict_types=1);

namespace PrestoWorld\Core\Legacy;

/**
 * LegacyInvoker — thực thi hooks (spec 06 §6.4 + 10 §10.4.11).
 *
 * - do_action / apply_filters compile thành trigger() (mode-s giữ nguyên,
 *   shim truyền hookType nhằm phân biệt action/filter).
 * - Action: chạy lần lượt theo priority (ổn định giữa cùng priority).
 * - Filter: nối chuỗi — giá trị mỗi hook trở thành tham số đầu của hook kế.
 */
class LegacyInvoker
{
    /** @var list<string> */
    private array $stack = [];

    /** @var array<string, int> */
    private array $didCount = [];

    public function __construct(private readonly LegacyRegistry $registry)
    {
    }

    /**
     * @param array<int, mixed> $hookArgs
     */
    public function trigger(string $tag, array $hookArgs = [], ?string $hookType = null): mixed
    {
        $type = $hookType ?? $this->inferType($tag);

        $this->stack[] = $tag;
        $this->didCount[$tag] = ($this->didCount[$tag] ?? 0) + 1;
        $isFilter = $type === LegacyRegistry::HOOK_FILTER;

        $hooks = $this->registry->hooksFor($tag, $type);
        $value = $hookArgs[0] ?? null;

        foreach ($hooks as $hook) {
            $callback = $hook['callback'];
            $args = $isFilter
                ? array_merge([$value], array_slice($hookArgs, 1))
                : $hookArgs;

            $args = $this->resolveArguments($callback, $args);

            $result = $this->invoke($callback, array_slice($args, 0, $hook['accepted_args']));

            if ($isFilter) {
                $value = $result;
            }
        }

        array_pop($this->stack);

        return $isFilter ? $value : null;
    }

    /**
     * Tag hiện đang được trigger (đỉnh stack), null nếu không trong hook.
     */
    public function current(): ?string
    {
        return $this->stack === [] ? null : $this->stack[count($this->stack) - 1];
    }

    public function isDoing(string $tag): bool
    {
        return in_array($tag, $this->stack, true);
    }

    /**
     * doing_action / doing_filter (spec 10 §10.4.11): không tag → có đang chạy
     * hook nào không; có tag → tag đó có trong stack không.
     */
    public function doing(?string $tag = null): bool
    {
        if ($tag === null) {
            return $this->current() !== null;
        }

        return $this->isDoing($tag);
    }

    public function did(string $tag): int
    {
        return $this->didCount[$tag] ?? 0;
    }

    public function reset(): void
    {
        $this->stack = [];
        $this->didCount = [];
    }

    private function inferType(string $tag): string
    {
        $filters = $this->registry->hooksFor($tag, LegacyRegistry::HOOK_FILTER);
        if ($filters !== []) {
            return LegacyRegistry::HOOK_FILTER;
        }

        return LegacyRegistry::HOOK_ACTION;
    }

    /**
     * Resolve callback (hàm global, closure, [Class, method]) thành callable.
     */
    private function resolve(callable $callback): callable
    {
        if (is_array($callback)) {
            $class = (string) $callback[0];
            $method = (string) $callback[1];

            if (class_exists($class) && !method_exists($class, $method)) {
                return $callback;
            }

            if (method_exists($class, $method)) {
                $ref = new \ReflectionMethod($class, $method);
                if ($ref->isStatic()) {
                    return [$class, $method];
                }

                $instance = $this->registryInstance($class);
                if ($instance !== null) {
                    return [$instance, $method];
                }
            }
        }

        return $callback;
    }

    /**
     * @param callable $callback
     * @param list<mixed> $args
     * @return mixed
     */
    private function invoke(callable $callback, array $args): mixed
    {
        try {
            return call_user_func_array($this->resolve($callback), $args);
        } catch (\Throwable $e) {
            // Callback lỗi không được làm sập request; báo qua log handler tuỳ chọn.
            trigger_error(
                sprintf('LegacyInvoker callback "%s" failed: %s', $this->describe($callback), $e->getMessage()),
                E_USER_WARNING,
            );

            return null;
        }
    }

    /**
     * Resolve tham số: dùng arg có sẵn theo vị trí; nếu thiếu và param có type
     * class + container resolve được thì inject; cuối cùng default/null.
     *
     * @param callable $callback
     * @param list<mixed> $args
     * @return list<mixed>
     */
    private function resolveArguments(callable $callback, array $args): array
    {
        try {
            $reflection = is_array($callback)
                ? new \ReflectionMethod((string) $callback[0], (string) $callback[1])
                : new \ReflectionFunction($callback);
        } catch (\ReflectionException) {
            return $args;
        }

        $params = $reflection->getParameters();
        if ($params === []) {
            return $args;
        }

        $result = [];
        foreach ($params as $index => $param) {
            if (array_key_exists($index, $args)) {
                $result[] = $args[$index];
                continue;
            }

            $type = $param->getType();
            if ($type instanceof \ReflectionNamedType && !$type->isBuiltin() && $type->getName() !== 'callable') {
                $service = $this->resolveService($type->getName());
                if ($service !== null) {
                    $result[] = $service;
                    continue;
                }
            }

            $result[] = $param->isDefaultValueAvailable() ? $param->getDefaultValue() : null;
        }

        return $result;
    }

    private function resolveService(string $class): mixed
    {
        if (!class_exists($class)) {
            return null;
        }

        $resolved = \PrestoWorld\Core\Support\App::make($class, null);
        return $resolved instanceof object ? $resolved : null;
    }

    private function registryInstance(string $class): ?object
    {
        return $this->resolveService($class);
    }

    private function describe(callable $callback): string
    {
        if (is_array($callback)) {
            return implode('@', array_map('strval', $callback));
        }

        if ($callback instanceof \Closure) {
            return 'closure';
        }

        return is_string($callback) ? $callback : get_class($callback);
    }
}