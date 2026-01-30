<?php
/**
 * Plugin Name: Legacy Sandbox Demo
 * Description: A plugin that uses "dangerous" WordPress patterns to demonstrate sandbox & transformer capabilities.
 */

// 1. Using global $wpdb (Will be transformed to app('wpdb'))
global $wpdb;

// 2. Using wp_options (Will be transformed to app('wp.options'))
$demo_count = get_option('sandbox_demo_count', 0);
update_option('sandbox_demo_count', $demo_count + 1);

// 3. Direct output (Will be captured by ResponseInterceptor)
add_action('wp_footer', function() use ($demo_count) {
    echo "<!-- Sandbox Demo: Active! Load Count: $demo_count -->";
    echo "<div style='position:fixed; bottom:20px; right:20px; background:#fff; padding:15px; border-radius:30px; box-shadow:0 10px 30px rgba(0,0,0,0.1); border:2px solid #6366f1; z-index:9999;'>";
    echo "🛡️ <strong>Sandboxed Legacy Plugin</strong> <span style='opacity:0.6;'>(Count: $demo_count)</span>";
    echo "</div>";
});

// 4. Using transients (Will be moved to native Cache)
set_transient('sandbox_last_tick', time(), 60);
