<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\Admin\AdminBar;

use PrestoWorld\Contracts\Admin\AdminBar\AdminBarContext as AdminBarContextContract;
use PrestoWorld\Contracts\Admin\AdminBar\AdminBarItem as AdminBarItemContract;

/**
 * Admin Bar Renderer for PrestoWorld Native
 *
 * Renders the admin bar for PrestoWorld native admin (PrestoModern skin).
 * Supports both light and dark themes.
 */
class AdminBarRenderer
{
    protected string $theme = 'light';

    protected array $defaultItems = [];

    public function __construct(protected AdminBarContextContract $context)
    {
        $this->defaultItems = $this->buildDefaultItems();
    }

    /**
     * Set theme (light or dark)
     */
    public function setTheme(string $theme): void
    {
        $this->theme = in_array($theme, ['light', 'dark'], true) ? $theme : 'light';
    }

    /**
     * Get current theme
     */
    public function getTheme(): string
    {
        return $this->theme;
    }

    /**
     * Render the admin bar HTML
     *
     * @param array<string, mixed> $user
     */
    public function render(array $user = []): string
    {
        $items = $this->context->getItems();
        $allItems = array_merge($this->defaultItems, $items);

        $themeClass = $this->theme === 'dark' ? 'presto-admin-bar--dark' : '';

        $html = '<div id="presto-adminbar" class="presto-admin-bar ' . $themeClass . '" role="navigation" aria-label="Admin Bar">';
        $html .= '<div class="presto-admin-bar-inner">';

        // Left side: logo + site name + main items
        $html .= '<ul class="presto-admin-bar-left">';
        $html .= $this->renderLogoItem();
        $html .= $this->renderSiteNameItem();
        foreach ($allItems as $item) {
            if ($this->isLeftItem($item)) {
                $html .= $this->renderItem($item);
            }
        }
        $html .= '</ul>';

        // Right side: user + secondary items
        $html .= '<ul class="presto-admin-bar-right">';
        foreach ($allItems as $item) {
            if (!$this->isLeftItem($item)) {
                $html .= $this->renderItem($item);
            }
        }
        $html .= $this->renderUserItem($user);
        $html .= '</ul>';

        $html .= '</div>';
        $html .= '</div>';

        return $html;
    }

    /**
     * Render admin bar as JSON (for SPA/CSR mode)
     *
     * @return array<string, mixed>
     */
    public function toArray(array $user = []): array
    {
        $items = $this->context->getItems();
        $allItems = array_merge($this->defaultItems, $items);

        return [
            'theme' => $this->theme,
            'items' => array_map(
                fn(AdminBarItemContract $item) => $item->toArray(),
                $allItems,
            ),
            'user' => $user,
        ];
    }

    /**
     * Render logo item
     */
    protected function renderLogoItem(): string
    {
        return '<li class="presto-admin-bar-item presto-admin-bar-logo">'
            . '<a href="/" class="presto-admin-bar-link" aria-label="PrestoWorld">'
            . '<span class="presto-admin-bar-icon" aria-hidden="true">'
            . '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">'
            . '<circle cx="12" cy="12" r="10"></circle>'
            . '<path d="M12 6v6l4 2"></path>'
            . '</svg>'
            . '</span>'
            . '<span class="screen-reader-text">About PrestoWorld</span>'
            . '</a>'
            . '</li>';
    }

    /**
     * Render site name item
     */
    protected function renderSiteNameItem(): string
    {
        return '<li class="presto-admin-bar-item presto-admin-bar-site-name">'
            . '<a href="/" class="presto-admin-bar-link">PrestoWorld</a>'
            . '</li>';
    }

    /**
     * Render user item
     *
     * @param array<string, mixed> $user
     */
    protected function renderUserItem(array $user): string
    {
        $name = $user['name'] ?? 'Admin';
        $avatar = $user['avatar'] ?? null;

        $avatarHtml = '';
        if ($avatar !== null) {
            $avatarHtml = '<img src="' . htmlspecialchars($avatar) . '" alt="" class="presto-admin-bar-avatar" />';
        }

        return '<li class="presto-admin-bar-item presto-admin-bar-user">'
            . '<a href="/profile" class="presto-admin-bar-link">'
            . $avatarHtml
            . '<span class="presto-admin-bar-username">Howdy, ' . htmlspecialchars($name) . '</span>'
            . '</a>'
            . '</li>';
    }

    /**
     * Render a single item
     */
    protected function renderItem(AdminBarItemContract $item): string
    {
        $id = $item->getId();
        $label = $item->getLabel();
        $icon = $item->getIcon();
        $href = $item->getHref() ?? '#';
        $type = $item->getType();
        $badge = $item->getBadge();
        $children = $item->getChildren();

        $classes = ['presto-admin-bar-item'];
        if ($children !== []) {
            $classes[] = 'presto-admin-bar-has-children';
        }

        $html = '<li id="presto-admin-bar-' . htmlspecialchars($id) . '" class="' . implode(' ', $classes) . '">';

        // Icon
        $iconHtml = '';
        if ($icon !== null) {
            $iconHtml = '<span class="presto-admin-bar-icon" aria-hidden="true">'
                . $this->renderIcon($icon)
                . '</span>';
        }

        // Badge
        $badgeHtml = '';
        if ($badge !== null) {
            $badgeHtml = '<span class="presto-admin-bar-badge">' . htmlspecialchars((string) $badge) . '</span>';
        }

        // Link/button
        if ($type === 'button') {
            $html .= '<button type="button" class="presto-admin-bar-link" data-action="' . htmlspecialchars($id) . '">';
            $html .= $iconHtml . htmlspecialchars($label) . $badgeHtml;
            $html .= '</button>';
        } else {
            $html .= '<a href="' . htmlspecialchars($href) . '" class="presto-admin-bar-link">';
            $html .= $iconHtml . htmlspecialchars($label) . $badgeHtml;
            $html .= '</a>';
        }

        // Children dropdown
        if ($children !== []) {
            $html .= '<ul class="presto-admin-bar-children">';
            foreach ($children as $child) {
                $html .= $this->renderItem($child);
            }
            $html .= '</ul>';
        }

        $html .= '</li>';

        return $html;
    }

    /**
     * Render icon by name
     */
    protected function renderIcon(string $icon): string
    {
        $icons = $this->getIcons();

        if (isset($icons[$icon])) {
            return $icons[$icon];
        }

        // Default icon
        return '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle></svg>';
    }

    /**
     * Get icon SVG map
     *
     * @return array<string, string>
     */
    protected function getIcons(): array
    {
        return [
            'Globe' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><path d="M2 12h20"></path><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path></svg>',
            'Bell' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>',
            'Plus' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>',
            'User' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>',
            'UserCheck' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="8.5" cy="7" r="4"></circle><polyline points="17 11 19 13 23 9"></polyline></svg>',
            'Settings' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>',
            'LayoutDashboard' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>',
            'FileText' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>',
            'Image' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline></svg>',
            'File' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M13 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"></path><polyline points="13 2 13 9 20 9"></polyline></svg>',
            'MessageSquare' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>',
            'Palette' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="13.5" cy="6.5" r="0.5"></circle><circle cx="17.5" cy="10.5" r="0.5"></circle><circle cx="8.5" cy="7.5" r="0.5"></circle><circle cx="6.5" cy="12.5" r="0.5"></circle><path d="M12 2C6.5 2 2 6.5 2 12s4.5 10 10 10c.926 0 1.648-.746 1.648-1.688 0-.437-.18-.835-.437-1.125-.29-.289-.438-.652-.438-1.125a1.64 1.64 0 0 1 1.668-1.668h1.996c3.051 0 5.555-2.503 5.555-5.554C21.965 6.012 17.461 2 12 2z"></path></svg>',
            'Puzzle' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19.439 7.85c-.049.322.059.648.289.878l1.568 1.568c.47.47.706 1.087.706 1.704s-.235 1.233-.706 1.704l-1.611 1.611a.98.98 0 0 1-.837.276c-.47-.07-.802-.48-.968-.925a2.501 2.501 0 1 0-3.214 3.214c.446.166.855.497.925.968a.979.979 0 0 1-.276.837l-1.61 1.61a2.404 2.404 0 0 1-1.705.707 2.402 2.402 0 0 1-1.704-.706l-1.568-1.568a1.026 1.026 0 0 0-.877-.29c-.493.074-.84.504-1.02.968a2.5 2.5 0 1 1-3.237-3.237c.464-.18.894-.527.967-1.02a1.026 1.026 0 0 0-.289-.877l-1.568-1.568A2.402 2.402 0 0 1 1.998 12c0-.617.236-1.234.706-1.704L4.23 8.77c.24-.24.581-.353.917-.303.515.077.877.528 1.073 1.01a2.5 2.5 0 1 0 3.259-3.259c-.482-.196-.933-.558-1.01-1.073-.05-.336.062-.676.303-.917l1.525-1.525A2.402 2.402 0 0 1 12 1.998c.617 0 1.234.236 1.704.706l1.568 1.568c.23.23.556.338.877.29.493-.074.84-.504 1.02-.968a2.5 2.5 0 1 1 3.237 3.237c-.464.18-.894.527-.967 1.02z"></path></svg>',
            'Wrench' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"></path></svg>',
            'Activity' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline></svg>',
            'Download' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>',
            'Upload' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg>',
            'Edit' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>',
            'BookOpen' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"></path><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"></path></svg>',
            'Tags' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"></path><line x1="7" y1="7" x2="7.01" y2="7"></line></svg>',
            'Menu' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="12" x2="21" y2="12"></line><line x1="3" y1="6" x2="21" y2="6"></line><line x1="3" y1="18" x2="21" y2="18"></line></svg>',
            'Code' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="16 18 22 12 16 6"></polyline><polyline points="8 6 2 12 8 18"></polyline></svg>',
            'Blocks' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"></rect><path d="M3 9h18"></path><path d="M9 21V9"></path></svg>',
            'Shield' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>',
            'Link' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"></path><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"></path></svg>',
        ];
    }

    /**
     * Build default admin bar items
     *
     * @return list<AdminBarItemContract>
     */
    protected function buildDefaultItems(): array
    {
        $items = [];

        // Visit Site
        $visitSite = new AdminBarItem(
            id: 'visit-site',
            label: 'Visit Site',
            icon: 'Globe',
            href: '/',
            type: 'link',
        );
        $items[] = $visitSite;

        // New Post
        $newPost = new AdminBarItem(
            id: 'new-post',
            label: 'New Post',
            icon: 'Plus',
            href: '/post-new',
            type: 'link',
        );
        $items[] = $newPost;

        // Comments
        $comments = new AdminBarItem(
            id: 'comments',
            label: 'Comments',
            icon: 'MessageSquare',
            href: '/edit-comments',
            type: 'link',
            badge: 0,
        );
        $items[] = $comments;

        return $items;
    }

    /**
     * Check if item should be on left side
     */
    protected function isLeftItem(AdminBarItemContract $item): bool
    {
        $leftIds = ['visit-site', 'new-post', 'comments'];

        return in_array($item->getId(), $leftIds, true);
    }
}
