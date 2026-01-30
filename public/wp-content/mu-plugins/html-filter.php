<?php
/**
 * MU-Plugin Name: HTML Post-Processor
 * Description: Uses PrestoWorld post-processing hooks to filter final HTML.
 */

// 1. Hook into the post-processing filter
add_filter('presto.response_body', function($html) {
    // Aggressive replacement for debugging
    $watermark = "
    <div id='presto-safety-badge' style='position:fixed; bottom:80px; left:20px; padding:10px 20px; background:rgba(0,0,0,0.9); color:#00ff88; font-family:monospace; border-radius:10px; z-index:1000000; backdrop-filter:blur(10px); border:1px solid #00ff88; box-shadow: 0 10px 30px rgba(0,0,0,0.5);'>
        ⚡ PrestoWorld Sandbox Active (Global)
    </div>";
    
    if (strpos($html, '</body>') !== false) {
        return str_replace('</body>', $watermark . '</body>', $html);
    }
    
    return $html . $watermark;
});

// 2. Demonstration actions
add_action('init', function() {
    error_log("[PrestoBridge] HTML Filter MU-Plugin initialized.");
});
