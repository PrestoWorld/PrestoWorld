<?php

declare(strict_types=1);

namespace PrestoWorld\Core\Admin\Adapters;

use PrestoWorld\Core\Admin\Contracts\AdminPageInterface;

class WpBridgeMenuAdapter
{
    public function register(AdminPageInterface $page): void
    {
        if (!function_exists('add_menu_page') && !function_exists('add_submenu_page')) {
            return;
        }

        $callback = $page->getRenderCallback();
        if ($callback === null) {
            $escAttr = function (string $value): string {
                if (function_exists('esc_attr')) {
                    return esc_attr($value);
                }
                return htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            };
            $callback = function () use ($page, $escAttr) {
                echo '<div id="presto-admin-root" data-page="' . $escAttr($page->getSlug()) . '"></div>';
            };
        }

        $parentSlug = $page->getParentSlug();
        if ($parentSlug === null || $parentSlug === '') {
            if (function_exists('add_menu_page')) {
                add_menu_page(
                    $page->getTitle(),
                    $page->getMenuTitle(),
                    $page->getCapability(),
                    $page->getSlug(),
                    $callback,
                    $page->getIcon(),
                    $page->getPosition()
                );
            }
        } else {
            if (function_exists('add_submenu_page')) {
                add_submenu_page(
                    $parentSlug,
                    $page->getTitle(),
                    $page->getMenuTitle(),
                    $page->getCapability(),
                    $page->getSlug(),
                    $callback,
                    $page->getPosition()
                );
            }
        }
    }
}
