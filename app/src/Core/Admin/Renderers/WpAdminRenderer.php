<?php

declare(strict_types=1);

namespace PrestoWorld\Core\Admin\Renderers;

use PrestoWorld\Core\Admin\Contracts\AdminContextInterface;
use PrestoWorld\Core\Admin\Contracts\AdminPageInterface;
use PrestoWorld\Core\Admin\Contracts\AdminRendererInterface;
use PrestoWorld\Core\Admin\Adapters\WpBridgeMenuAdapter;

class WpAdminRenderer extends AbstractAdminRenderer
{
    public function __construct(protected WpBridgeMenuAdapter $adapter)
    {
    }

    public function supports(AdminContextInterface $context): bool
    {
        return $context->isWpAdmin();
    }

    public function registerMenu(AdminPageInterface $page): void
    {
        $this->adapter->register($page);
    }

    public function render(AdminPageInterface $page, array $data = []): mixed
    {
        $callback = $page->getRenderCallback();
        if (is_callable($callback)) {
            return $callback($page, $data);
        }

        $escAttr = function (string $value): string {
            if (function_exists('esc_attr')) {
                return esc_attr($value);
            }
            return htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        };

        echo '<div id="presto-admin-root" data-page="' . $escAttr($page->getSlug()) . '" data-context="wp-admin"></div>';
        return null;
    }
}
