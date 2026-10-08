<?php
/**
 * Plugin Name: Demo Widget
 * Plugin URI:  https://example.com/demo-widget
 * Description: Demo plugin để smoke-test WpCompiler.
 * Version:     1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;

function dw_get_products(int $limit = 10): array
{
    global $wpdb;

    return $wpdb->get_results(
        "SELECT post_title FROM {$wpdb->prefix}posts WHERE post_type = 'product' LIMIT {$limit}"
    );
}

function dw_render_widget(): void
{
    $title = get_option('dw_widget_title', 'Demo');
    echo '<h2>' . esc_html($title) . '</h2>';

    foreach (dw_get_products() as $post) {
        echo '<li>' . esc_html($post->post_title) . '</li>';
    }
}

add_action('wp_enqueue_scripts', function () {
    wp_enqueue_style('dw-style', plugins_url('assets/style.css', __FILE__));
});

function dw_theme_setup(): void
{
    add_theme_support('post-thumbnails');
}

if (!function_exists('dw_legacy_helper')) {
    function dw_legacy_helper(string $text): string
    {
        return sanitize_text_field($text);
    }
}

$handle = new WP_Query(['post_type' => 'product', 'posts_per_page' => 5]);
if ($handle->have_posts()) {
    wp_die('Preview not supported');
}