<?php

declare(strict_types=1);

namespace PrestoWorld\Core\Admin;

use PrestoWorld\Core\Admin\Contracts\AdminContextInterface;
use PrestoWorld\Core\Admin\Contracts\AdminPageInterface;
use PrestoWorld\Core\Admin\Contracts\AdminRegistryInterface;
use PrestoWorld\Core\Admin\Contracts\AdminRendererInterface;

class AdminDashboardManager
{
    /** @var AdminRendererInterface[] */
    protected array $renderers = [];

    public function __construct(
        protected AdminContextInterface $context,
        protected AdminRegistryInterface $registry,
        array $renderers = []
    ) {
        foreach ($renderers as $renderer) {
            $this->registerRenderer($renderer);
        }
    }

    public function registerRenderer(AdminRendererInterface $renderer): void
    {
        $this->renderers[] = $renderer;
    }

    public function registerPage(AdminPageInterface $page): void
    {
        $this->registry->registerPage($page);
        $this->syncMenu($page);
    }

    protected function syncMenu(AdminPageInterface $page): void
    {
        foreach ($this->renderers as $renderer) {
            if ($renderer->supports($this->context)) {
                $renderer->registerMenu($page);
                return;
            }
        }
    }

    public function render(string $slug, array $data = []): mixed
    {
        $page = $this->registry->getPage($slug);
        if ($page === null) {
            return null;
        }

        foreach ($this->renderers as $renderer) {
            if ($renderer->supports($this->context)) {
                return $renderer->render($page, $data);
            }
        }

        // Fallback to first renderer
        if (isset($this->renderers[0])) {
            return $this->renderers[0]->render($page, $data);
        }

        return null;
    }

    public function getMenuTree(): array
    {
        return $this->registry->getMenuTree();
    }

    public function getContext(): AdminContextInterface
    {
        return $this->context;
    }

    public function getRegistry(): AdminRegistryInterface
    {
        return $this->registry;
    }
}
