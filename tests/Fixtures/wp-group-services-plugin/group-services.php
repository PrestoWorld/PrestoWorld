<?php
/**
 * Plugin Name: Group Services Probe
 * Plugin URI:  https://example.com/group-services
 * Description: Fixture kiểm chứng compiler rewrite cho Groups 5/14/15/16/17.
 * Version:     1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

function gs_probe_post(int $id): string
{
    the_post();

    return get_the_title($id) . get_post_meta($id, '_probe', true);
}

/**
 * @return array<int, mixed>
 */
function gs_probe_terms(): array
{
    return get_terms(['taxonomy' => 'category']);
}

/**
 * @return array<int, mixed>
 */
function gs_probe_comments(): array
{
    return get_comments(['post_id' => 1]);
}

function gs_probe_insert(): int
{
    return wp_insert_post(['post_title' => 'Probe', 'post_type' => 'post']);
}

/**
 * @return array<string, mixed>
 */
function gs_probe_upload(): array
{
    return wp_upload_bits('probe.txt', null, 'hello');
}

function gs_probe_schedule(): bool
{
    return wp_schedule_single_event(time() + 60, 'gs_probe_event');
}

function gs_probe_rest(mixed $response): mixed
{
    return rest_ensure_response($response);
}

function gs_probe_media(int $id): void
{
    wp_create_image_subsizes($id);
}
