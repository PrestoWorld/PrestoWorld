<?php

declare(strict_types=1);

namespace PrestoWorld\Core\Admin\Contracts;

interface AdminRendererInterface
{
    public function supports(AdminContextInterface $context): bool;

    public function render(AdminPageInterface $page, array $data = []): mixed;

    public function registerMenu(AdminPageInterface $page): void;
}
