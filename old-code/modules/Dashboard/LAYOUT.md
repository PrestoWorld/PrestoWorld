# PrestoWorld Admin Dashboard Layout

This document describes the layout structure, design system, and extensibility points for the PrestoWorld Admin interface.

## 1. Structure Overview

The admin layout is a two-column structure with a fixed sidebar and a fluid main content area.

- **Sidebar (`.presto-sidebar`)**: Contains branding, navigation menu, and user profile.
- **Main Wrapper (`.presto-main-wrapper`)**:
    - **Header (`.presto-main-header`)**: Contains breadcrumbs, global search, notifications, and primary action buttons.
    - **Content Area (`.presto-content-area`)**: The primary stage for module-specific content.
    - **Footer (`.presto-admin-footer`)**: System versioning and framework attribution.

---

## 2. Design System

PrestoWorld uses a premium dark-mode aesthetic built with Vanilla CSS.

### CSS Variables
Core colors and spacing are managed via CSS variables in `:root`:
- `--primary`: Indigo highlight (`#6366f1`).
- `--bg-side`: Dark sidebar background (`#0b0e14`).
- `--bg-deep`: Deep abyss background for content area (`#06080c`).
- `--border`: Subdued border color.
- `--text-main`: High-contrast text for headers.
- `--text-dim`: Secondary text for navigation.

### Essential Components
- **Cards (`.presto-card`)**: Glassmorphism containers with blur and subtle hover shifts.
- **Buttons**:
    - `.presto-btn-primary`: Vibrant indigo button with glow.
    - `.presto-btn-secondary`: Discreet outlined button.
- **Notifications (`.presto-notice`)**: Categorized by type (`.presto-notice-success`, `-info`, `-error`, `-warning`).

---

## 3. Placeholders & Hooks (Extensibility)

Themes and plugins can inject content into predefined "zones" using the standard `add_action()` system.

### Header Hooks
- `admin.header.actions.before`: Before the search input.
- `admin.header.actions.after`: After the "Add New" button.

### Sidebar Hooks
- `admin.sidebar.top`: Top of the sidebar, above branding.
- `admin.sidebar.bottom`: Below the navigation menu.
- `admin.nav.before`: Directly above the dynamic navigation list.
- `admin.nav.after`: Directly below the dynamic navigation list.

### Content Area Hooks
- `admin.content.top`: Inside the content area, above the page title.
- `admin.content.bottom`: Above the footer.

### Footer Hooks
- `admin.footer.left`: Left side of the footer (attribution area).
- `admin.footer.right`: Right side of the footer.

### Dashboard-Specific Hooks
- `admin.dashboard.top`: Top of the main dashboard index.
- `admin.dashboard.widgets.before`: Before the interactive widgets.
- `admin.dashboard.widgets.after`: After the interactive widgets.
- `admin.dashboard.bottom`: Bottom of the dashboard.

---

## 4. Usage Example

To inject a custom widget or message from a plugin:

```php
add_action('admin.dashboard.top', function() {
    echo '<div class="presto-card" style="padding: 20px; border-left: 4px solid var(--primary);">
            <h4>Plugin Insight</h4>
            <p>Your custom module data can go here!</p>
          </div>';
});
```

To add a new navigation menu group:
1. Register a new `DropdownGroupContext` or `MenuItemContext` under the `dashboard.menu` context.
2. Use priorities to control the order (e.g., `priority: 50`).

---

## 5. File Locations
- **Layout Template**: `resources/views/admin/layout.stempler.php`
- **Main CSS**: `public/css/admin-dashboard.css`
- **Controller Logic**: `app/Foundation/Admin/AdminController.php`
- **Dashboard Logic**: `modules/Dashboard/src/Controllers/DashboardController.php`
