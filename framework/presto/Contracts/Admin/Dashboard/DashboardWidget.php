<?php

declare(strict_types=1);

namespace PrestoWorld\Contracts\Admin\Dashboard;

interface DashboardWidget
{
    public function getId(): string;
    public function getTitle(): string;
    public function getContent(): string;
    public function getPriority(): int;
    public function getColumn(): int;
    public function getContext(): string;
    public function isVisible(): bool;

    /** SPA component name to render (e.g. "StatCards", "QuickDraft", "ActivityLog") */
    public function getComponent(): string;

    /** Grid span: "full", "half", "third", "quarter" */
    public function getGrid(): string;

    /** Arbitrary props passed to the SPA component */
    public function getProps(): array;

    /** Set SPA component name */
    public function setComponent(string $component): self;

    /** Set grid span */
    public function setGrid(string $grid): self;

    /** Set arbitrary props */
    public function setProps(array $props): self;

    public function toArray(): array;
}
