<?php
/**
 * MU-Plugin Name: Legacy MU Demo
 * Description: Demonstrates auto-loading of MU-plugins via sandbox.
 */

add_action('init', function() {
    // This runs in a long-running process like RoadRunner
    // But it's safe because of IsolationSandbox backup/restore
    global $mu_demo_active;
    $mu_demo_active = true;
});

add_action('wp_footer', function() {
    echo "<!-- Legacy MU-Plugin Loaded via Sandbox -->";
});

// New: Filter the final HTML output
add_filter('presto.response_body', function($html) {
    if (strpos($html, '<body') !== false) {
        $badge = '<div style="background: #ff0055; color: white; padding: 5px 10px; border-radius: 5px; position: fixed; top: 10px; right: 10px; font-weight: bold; z-index: 10000; box-shadow: 0 0 10px rgba(0,0,0,0.5);">WordPress Sandbox Active</div>';
        $html = str_replace('</body>', $badge . '</body>', $html);
    }
    return $html;
});
