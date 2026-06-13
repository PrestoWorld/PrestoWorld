<?php

declare(strict_types=1);

namespace PrestoWorld\Contracts\Admin;

interface SkinInterface
{
    public const MODE_SSR = 'ssr';
    public const MODE_CSR = 'csr';

    public function getName(): string;

    public function getRenderMode(): string;

    public function renderLayout(string $content, array $args = []): string;

    public function renderComponent(string $component, array $props = []): string;

    public function getAssets(): array;
}
