<?php

declare(strict_types=1);

namespace PrestoWorld\Core\Admin\Context;

use PrestoWorld\Core\Admin\Contracts\WpScreenInterface;

/**
 * WordPress-compatible screen object (WP_Screen subset).
 *
 * Immutable value object holding the current admin screen context.
 */
class WpScreen implements WpScreenInterface
{
    public function __construct(
        protected ?string $id = null,
        protected ?string $base = null,
        protected ?string $action = null,
        protected ?string $postType = null,
        protected ?string $parentFile = null,
        protected ?string $parentBase = null,
        protected bool $isNetwork = false,
        protected bool $isUserAdmin = false,
    ) {}

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getBase(): ?string
    {
        return $this->base;
    }

    public function getAction(): ?string
    {
        return $this->action;
    }

    public function getPostType(): ?string
    {
        return $this->postType;
    }

    public function getParentFile(): ?string
    {
        return $this->parentFile;
    }

    public function getParentBase(): ?string
    {
        return $this->parentBase;
    }

    public function isNetwork(): bool
    {
        return $this->isNetwork;
    }

    public function isUserAdmin(): bool
    {
        return $this->isUserAdmin;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'base' => $this->base,
            'action' => $this->action,
            'post_type' => $this->postType,
            'parent_file' => $this->parentFile,
            'parent_base' => $this->parentBase,
            'is_network' => $this->isNetwork,
            'is_user_admin' => $this->isUserAdmin,
        ];
    }
}
