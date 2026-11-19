<?php

declare(strict_types=1);

namespace PrestoWorld\Core\Admin\Contracts;

/**
 * Contract for the WordPress-compatible current screen state.
 *
 * WordPress plugins call get_current_screen() to inspect the admin page
 * context (id, base, post_type, action, ...). PrestoWorld implements this
 * as a request-scoped service so it is safe on long-running runtimes
 * (RoadRunner / FrankenPHP) — no static state that leaks between requests.
 */
interface CurrentScreenInterface
{
    public function setScreen(?string $screenId, array $meta = []): void;

    public function getScreen(): ?WpScreenInterface;

    public function clearScreen(): void;

    public function hasScreen(): bool;
}

/**
 * WordPress-compatible screen object (WP_Screen subset).
 */
interface WpScreenInterface
{
    public function getId(): ?string;

    public function getBase(): ?string;

    public function getAction(): ?string;

    public function getPostType(): ?string;

    public function getParentFile(): ?string;

    public function getParentBase(): ?string;

    public function isNetwork(): bool;

    public function isUserAdmin(): bool;

    public function toArray(): array;
}
