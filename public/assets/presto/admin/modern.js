/**
 * PrestoWorld Admin Bar — PrestoModern Skin
 *
 * Handles admin bar interactions:
 * - Dropdown menus
 * - Theme toggle
 * - Keyboard navigation
 */

(function () {
    'use strict';

    var adminBar = document.getElementById('presto-adminbar');
    if (!adminBar) return;

    // Dropdown toggle
    var dropdownItems = adminBar.querySelectorAll('.presto-admin-bar-has-children');
    dropdownItems.forEach(function (item) {
        var link = item.querySelector('.presto-admin-bar-link');
        if (!link) return;

        link.addEventListener('click', function (e) {
            var children = item.querySelector('.presto-admin-bar-children');
            if (!children) return;

            var isVisible = children.style.display === 'block';
            // Close all other dropdowns
            adminBar.querySelectorAll('.presto-admin-bar-children').forEach(function (c) {
                c.style.display = 'none';
            });

            if (!isVisible) {
                children.style.display = 'block';
                e.preventDefault();
                e.stopPropagation();
            }
        });
    });

    // Close dropdowns on outside click
    document.addEventListener('click', function () {
        adminBar.querySelectorAll('.presto-admin-bar-children').forEach(function (c) {
            c.style.display = 'none';
        });
    });

    // Keyboard navigation
    adminBar.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            adminBar.querySelectorAll('.presto-admin-bar-children').forEach(function (c) {
                c.style.display = 'none';
            });
        }

        if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
            var focused = document.activeElement;
            if (!focused || !focused.classList.contains('presto-admin-bar-link')) return;

            var parent = focused.closest('.presto-admin-bar-item');
            if (!parent) return;

            var children = parent.querySelector('.presto-admin-bar-children');
            if (!children) return;

            var links = children.querySelectorAll('.presto-admin-bar-link');
            if (links.length === 0) return;

            e.preventDefault();
            if (e.key === 'ArrowDown') {
                links[0].focus();
            } else {
                links[links.length - 1].focus();
            }
        }
    });

    // Theme toggle (if theme switcher exists)
    var themeToggle = adminBar.querySelector('[data-action="toggle-theme"]');
    if (themeToggle) {
        themeToggle.addEventListener('click', function () {
            var body = document.body;
            var isDark = body.classList.toggle('presto-admin-dark');
            themeToggle.setAttribute('aria-pressed', String(isDark));
        });
    }
})();
