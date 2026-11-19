<?php

declare(strict_types=1);

namespace PrestoWorld\Core\Admin;

/**
 * WordPress admin menu shims backed by PrestoWorld's MenuContextRepository.
 *
 * WordPress plugins (e.g. Yoast SEO) call add_menu_page() / add_submenu_page()
 * to register admin menus. These shims accept the same signatures but store
 * registrations in MenuContextRepository so they appear in the BookPress
 * wp-admin UI rendered by WpAdminController / BookPressWpAdminController.
 */
if (!function_exists('add_menu_page')) {
    /**
     * @param string $page_title
     * @param string $menu_title
     * @param string $capability
     * @param string $menu_slug
     * @param callable|null $callback
     * @param string $icon_url
     * @param int|float|null $position
     * @return string
     */
    function add_menu_page(
        string $page_title,
        string $menu_title,
        string $capability,
        string $menu_slug,
        $callback = '',
        string $icon_url = '',
        $position = null
    ): string {
        $menu = _presto_wp_admin_menu_repository();
        if ($menu === null) {
            return $menu_slug;
        }

        $groupId = 'menu-' . _presto_sanitize_key($menu_slug !== '' ? $menu_slug : $menu_title);
        $priority = is_numeric($position) ? (int) $position : 100;
        $url = $menu_slug;
        $screenId = $menu_slug;

        $menu->registerGroup(
            $groupId,
            $menu_title,
            $icon_url !== '' ? $icon_url : 'dashicons-admin-generic',
            null,
            [
                'section' => 'wp-plugins',
                'url' => $url,
                'source' => 'wp-shim',
                'page_title' => $page_title,
                'capability' => $capability,
            ],
            $priority
        );

        $menu->registerItem(
            $groupId,
            $menu_title,
            $url,
            $icon_url !== '' ? $icon_url : 'dashicons-admin-generic',
            null,
            [
                'page_title' => $page_title,
                'capability' => $capability,
                'callback' => is_callable($callback) ? $callback : null,
            ],
            0,
            $groupId . '-primary',
            $screenId
        );

        return $menu_slug;
    }
}

if (!function_exists('add_submenu_page')) {
    /**
     * @param string $parent_slug
     * @param string $page_title
     * @param string $menu_title
     * @param string $capability
     * @param string $menu_slug
     * @param callable|null $callback
     * @param int|float|null $position
     * @return string|false
     */
    function add_submenu_page(
        string $parent_slug,
        string $page_title,
        string $menu_title,
        string $capability,
        string $menu_slug,
        $callback = null,
        $position = null
    ): string|false {
        $menu = _presto_wp_admin_menu_repository();
        if ($menu === null) {
            return $menu_slug;
        }

        $parentGroup = $parent_slug;
        if (!$menu->has($parentGroup)) {
            $parentGroup = 'menu-' . _presto_sanitize_key($parent_slug);
        }
        if (!$menu->has($parentGroup)) {
            add_menu_page($parent_slug, $parent_slug, $capability, $parent_slug);
            $parentGroup = 'menu-' . _presto_sanitize_key($parent_slug);
        }

        $menu->registerItem(
            $parentGroup,
            $menu_title,
            $menu_slug,
            null,
            null,
            [
                'page_title' => $page_title,
                'capability' => $capability,
                'callback' => is_callable($callback) ? $callback : null,
            ],
            is_numeric($position) ? (int) $position : 10,
            'submenu-' . _presto_sanitize_key($menu_slug),
            $menu_slug
        );

        return $menu_slug;
    }
}

if (!function_exists('add_options_page')) {
    /**
     * @param string $page_title
     * @param string $menu_title
     * @param string $capability
     * @param string $menu_slug
     * @param callable|null $callback
     * @param int|float|null $position
     * @return string
     */
    function add_options_page(
        string $page_title,
        string $menu_title,
        string $capability,
        string $menu_slug,
        $callback = '',
        $position = null
    ): string {
        return add_submenu_page('options-general.php', $page_title, $menu_title, $capability, $menu_slug, $callback, $position);
    }
}

if (!function_exists('add_theme_page')) {
    /**
     * @param string $page_title
     * @param string $menu_title
     * @param string $capability
     * @param string $menu_slug
     * @param callable|null $callback
     * @param int|float|null $position
     * @return string
     */
    function add_theme_page(
        string $page_title,
        string $menu_title,
        string $capability,
        string $menu_slug,
        $callback = '',
        $position = null
    ): string {
        return add_submenu_page('themes.php', $page_title, $menu_title, $capability, $menu_slug, $callback, $position);
    }
}

if (!function_exists('add_management_page')) {
    /**
     * @param string $page_title
     * @param string $menu_title
     * @param string $capability
     * @param string $menu_slug
     * @param callable|null $callback
     * @param int|float|null $position
     * @return string
     */
    function add_management_page(
        string $page_title,
        string $menu_title,
        string $capability,
        string $menu_slug,
        $callback = '',
        $position = null
    ): string {
        return add_submenu_page('tools.php', $page_title, $menu_title, $capability, $menu_slug, $callback, $position);
    }
}

if (!function_exists('remove_menu_page')) {
    function remove_menu_page(string $menu_slug): array|false
    {
        $menu = _presto_wp_admin_menu_repository();
        if ($menu === null) {
            return false;
        }
        $id = 'menu-' . _presto_sanitize_key($menu_slug);
        if ($menu->has($id)) {
            $menu->remove($id);
            return ['menu_slug' => $menu_slug];
        }
        return false;
    }
}

if (!function_exists('remove_submenu_page')) {
    function remove_submenu_page(string $menu_slug, string $submenu_slug): array|false
    {
        $menu = _presto_wp_admin_menu_repository();
        if ($menu === null) {
            return false;
        }
        $id = 'submenu-' . _presto_sanitize_key($submenu_slug);
        if ($menu->has($id)) {
            $menu->remove($id);
            return ['menu_slug' => $menu_slug, 'submenu_slug' => $submenu_slug];
        }
        return false;
    }
}

if (!function_exists('_presto_wp_admin_menu_repository')) {
    /**
     * @return \PrestoWorld\Contracts\Admin\Menu\MenuContextRepository|null
     */
    function _presto_wp_admin_menu_repository(): ?\PrestoWorld\Contracts\Admin\Menu\MenuContextRepository
    {
        try {
            $app = function_exists('app') ? app() : null;
            if ($app === null || !method_exists($app, 'has') || !$app->has(\PrestoWorld\Contracts\Admin\Menu\MenuContextRepository::class)) {
                return null;
            }
            $repo = $app->make(\PrestoWorld\Contracts\Admin\Menu\MenuContextRepository::class);
            return $repo instanceof \PrestoWorld\Contracts\Admin\Menu\MenuContextRepository ? $repo : null;
        } catch (\Throwable $e) {
            return null;
        }
    }
}

if (!function_exists('_presto_sanitize_key')) {
    function _presto_sanitize_key(string $key): string
    {
        $key = strtolower($key);
        $key = preg_replace('/[^a-z0-9_-]/', '-', $key);
        $key = preg_replace('/-+/', '-', $key);
        return trim($key, '-');
    }
}

if (!function_exists('get_current_screen')) {
    /**
     * WordPress-compatible get_current_screen() shim.
     *
     * Returns a WP_Screen-compatible object describing the current admin
     * screen, or null if not on a wp-admin page. Backed by
     * CurrentScreenService (request-scoped, Roadrunner-safe).
     *
     * @return \PrestoWorld\Core\Admin\Contracts\WpScreenInterface|null
     */
    function get_current_screen(): ?\PrestoWorld\Core\Admin\Contracts\WpScreenInterface
    {
        try {
            $app = function_exists('app') ? app() : null;
            if ($app === null || !method_exists($app, 'has') || !$app->has(\PrestoWorld\Core\Admin\Contracts\CurrentScreenInterface::class)) {
                return null;
            }
            /** @var \PrestoWorld\Core\Admin\Contracts\CurrentScreenInterface $service */
            $service = $app->make(\PrestoWorld\Core\Admin\Contracts\CurrentScreenInterface::class);
            return $service instanceof \PrestoWorld\Core\Admin\Contracts\CurrentScreenInterface ? $service->getScreen() : null;
        } catch (\Throwable $e) {
            return null;
        }
    }
}
