<?php

declare(strict_types=1);

namespace PrestoWorld\Core\Admin\Renderers;

use PrestoWorld\Core\Admin\Contracts\AdminContextInterface;
use PrestoWorld\Core\Admin\Contracts\AdminPageInterface;

class SpaDashboardRenderer extends AbstractAdminRenderer
{
    public function supports(AdminContextInterface $context): bool
    {
        return $context->isSpaDashboard();
    }

    public function registerMenu(AdminPageInterface $page): void
    {
        // Menu is consumed from registry by SPA API/controllers
    }

    public function render(AdminPageInterface $page, array $data = []): mixed
    {
        $component = $page->getSpaComponent() ?? $page->getSlug();
        $payload = array_merge($data, [
            'page' => $page->getSlug(),
            'component' => $component,
            'title' => $page->getTitle(),
        ]);

        $escAttr = function (string $value): string {
            if (function_exists('esc_attr')) {
                return esc_attr($value);
            }
            return htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        };

        $jsonEncode = function (array $value): string {
            if (function_exists('wp_json_encode')) {
                return wp_json_encode($value, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
            }
            return json_encode($value, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
        };

        echo '<div id="presto-dashboard-app" data-page="' . $escAttr($page->getSlug()) . '" data-component="' . $escAttr($component) . '"></div>';
        echo '<script>window.__PRESTO_DASHBOARD__ = ' . $jsonEncode($payload) . ';</script>';
        return null;
    }
}
