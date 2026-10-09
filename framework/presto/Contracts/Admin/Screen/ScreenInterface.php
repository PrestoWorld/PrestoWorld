<?php

declare(strict_types=1);

namespace PrestoWorld\Contracts\Admin\Screen;

interface ScreenInterface
{
    public function getId(): string;
    public function getTitle(): string;
    public function getParent(): ?string;
    public function getCapability(): ?string;
    public function getIcon(): ?string;
    public function getPosition(): int;

    /**
     * Identifier of the React component registered on the dashboard SPA.
     * Null means the SPA should render its generic screen fallback.
     */
    public function getComponent(): ?string;

    /**
     * Origin of the registration: core, module, plugin or theme.
     */
    public function getSource(): string;

    /**
     * Arbitrary extra configuration passed through to the SPA.
     *
     * @return array<string, mixed>
     */
    public function getSettings(): array;

    public function toArray(): array;
}
