<?php

declare(strict_types=1);

namespace PrestoWorld\Core\Post;

/**
 * Embed — WP_Embed replacement (spec 10 §10.5.4).
 */
final class Embed
{
    /** @var array<string, array{regex: string, callback: callable|null}> */
    private array $handlers = [];

    public function __construct()
    {
    }

    public function register(string $handle, string $regex, ?callable $callback = null): bool
    {
        $this->handlers[$handle] = ['regex' => $regex, 'callback' => $callback];

        return true;
    }

    public function unregister(string $handle): bool
    {
        if (!isset($this->handlers[$handle])) {
            return false;
        }

        unset($this->handlers[$handle]);

        return true;
    }

    public function unregisterAll(): void
    {
        $this->handlers = [];
    }

    public function matches(mixed $url): bool
    {
        if (!is_string($url)) {
            return false;
        }

        foreach ($this->handlers as $handler) {
            if (preg_match($handler['regex'], $url) === 1) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<string, mixed> $args
     */
    public function get(string $url = '', array $args = []): string
    {
        if ($url === '' || !$this->matches($url)) {
            return '';
        }

        return '<a href="' . $url . '">' . $url . '</a>';
    }
}
