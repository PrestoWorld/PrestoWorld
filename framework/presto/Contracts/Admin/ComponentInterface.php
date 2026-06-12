<?php

declare(strict_types=1);

namespace PrestoWorld\Contracts\Admin;

interface ComponentInterface
{
    /**
     * Render the component to HTML.
     */
    public function render(): string;

    /**
     * Set properties for the component.
     */
    public function with(array $props): self;
}
