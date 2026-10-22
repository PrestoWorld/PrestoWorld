<?php

declare(strict_types=1);

namespace App\Http\Controllers\WpRest;

use Witals\Framework\Http\Request;
use Witals\Framework\Http\Response;

/**
 * `OPTIONS /wp-json/*` — the capability probes `@wordpress/core-data` uses.
 *
 * `canUser()` fires a single `OPTIONS` request per resource and derives
 * `create/read/update/delete` from the `Allow` header it comes back with; a
 * 404 just leaves the permission unset, which disables save/delete affordances
 * in the Site Editor.
 *
 * `/wp/v2/settings` additionally answers with a WP-style body whose
 * `schema.properties.*.title` values are turned into the labels of the site
 * settings form.
 */
class OptionsController extends RestController
{
    /** GET-shaped handler registered for the OPTIONS method on every REST path. */
    public function handle(Request $request): Response
    {
        // Keep the path as it arrived: entity ids are percent-encoded
        // (`timeless%2F%2Findex`) and decoding them here would introduce the
        // literal `//` the patterns below can no longer match.
        $path = ltrim($request->path(), '/');
        $rel = preg_replace('#^wp-json/#', '', $path) ?? $path;

        $allow = $this->allowFor($rel);
        $headers = ['Allow' => implode(', ', $allow)];

        if ($rel === 'wp/v2/settings') {
            return $this->respond($this->settingsBody($allow), 200, $headers);
        }

        if ($allow === []) {
            return $this->error('rest_no_route', 'Invalid route.', 404);
        }

        return $this->respond((object) [], 200, $headers);
    }

    /**
     * @return list<string>
     */
    private function allowFor(string $rel): array
    {
        $routes = [
            'wp/v2/settings' => ['GET', 'POST', 'PUT', 'PATCH'],
            'wp/v2/view-config' => ['GET'],
            'wp/v2/themes' => ['GET'],
            'wp/v2/themes/[^/]+' => ['GET'],
            'wp/v2/templates' => ['GET', 'POST'],
            'wp/v2/templates/lookup' => ['GET'],
            'wp/v2/templates/.+/revisions' => ['GET'],
            // Ids are `stylesheet//slug` — the slashes are literal in the
            // requests the editor sends, so the probe pattern must span them.
            'wp/v2/templates/.+' => ['GET', 'PUT', 'PATCH', 'DELETE'],
            'wp/v2/template-parts' => ['GET', 'POST'],
            'wp/v2/template-parts/lookup' => ['GET'],
            'wp/v2/template-parts/.+/revisions' => ['GET'],
            'wp/v2/template-parts/.+' => ['GET', 'PUT', 'PATCH', 'DELETE'],
            'wp/v2/global-styles/[^/]+/revisions' => ['GET'],
            'wp/v2/global-styles/1' => ['GET', 'POST', 'PUT'],
            'wp/v2/global-styles/themes/[^/]+' => ['GET'],
            'wp/v2/global-styles/themes/[^/]+/variations' => ['GET'],
            'wp/v2/types' => ['GET'],
            'wp/v2/types/[^/]+' => ['GET'],
            'wp/v2/taxonomies' => ['GET'],
            'wp/v2/taxonomies/[^/]+' => ['GET'],
            'wp/v2/block-patterns/patterns' => ['GET'],
            'wp/v2/block-patterns/categories' => ['GET'],
            'wp/v2/posts' => ['GET'],
            'wp/v2/posts/[^/]+' => ['GET'],
            'wp/v2/pages' => ['GET'],
            'wp/v2/pages/[^/]+' => ['GET'],
            'wp/v2/blocks' => ['GET'],
            'wp/v2/blocks/[^/]+' => ['GET'],
            'wp/v2/navigation' => ['GET'],
            'wp/v2/navigation/[^/]+' => ['GET'],
            'wp/v2/media' => ['GET'],
            'wp/v2/media/[^/]+' => ['GET'],
            'wp/v2/categories' => ['GET'],
            'wp/v2/tags' => ['GET'],
            'wp/v2/users' => ['GET'],
            'wp/v2/users/me' => ['GET', 'POST', 'PUT'],
            'wp-block-editor/v1/settings' => ['GET'],
            'wp-block-editor/v1/assets' => ['GET'],
            'wp-block-editor/v1/navigation-fallback' => ['GET'],
            // Revisions probes (posts/pages/blocks/navigation/media/global-styles)
            'wp/v2/[^/]+/[^/]+/revisions' => ['GET'],
        ];

        foreach ($routes as $pattern => $allow) {
            if (preg_match('#^' . $pattern . '$#', $rel) === 1) {
                return $allow;
            }
        }

        return [];
    }

    /**
     * @param list<string> $allow
     * @return array<string, mixed>
     */
    private function settingsBody(array $allow): array
    {
        $settings = $this->siteSettings();
        $titles = [
            'title' => 'Site Title',
            'description' => 'Tagline',
            'url' => 'Site Address (URL)',
            'home' => 'WordPress Address (URL)',
            'show_on_front' => 'Homepage displays',
            'page_on_front' => 'Homepage',
            'page_for_posts' => 'Posts page',
            'posts_per_page' => 'Blog pages show at most',
            'date_format' => 'Date Format',
            'time_format' => 'Time Format',
            'timezone_string' => 'Timezone',
            'language' => 'Site Language',
            'start_of_week' => 'Week Starts On',
            'default_category' => 'Default Category',
            'default_post_format' => 'Default Post Format',
            'use_smilies' => 'Convert emoticons',
            'require_name_email' => 'Require name and email',
            'comment_moderation' => 'Hold a comment for moderation',
            'comment_previously_approved' => 'Comment author must have a previously approved comment',
            'default_comment_status' => 'Allow people to submit comments',
            'default_ping_status' => 'Allow link notifications',
            'blog_public' => 'Search engine visibility',
            'thumbnail_size_w' => 'Thumbnail width',
            'thumbnail_size_h' => 'Thumbnail height',
            'medium_size_w' => 'Medium size width',
            'medium_size_h' => 'Medium size height',
            'large_size_w' => 'Large size width',
            'large_size_h' => 'Large size height',
        ];

        $properties = [];
        foreach ($titles as $key => $title) {
            if (!array_key_exists($key, $settings)) {
                $settings[$key] = $key === 'blog_public' ? 1 : ($key === 'show_on_front' ? 'posts' : '');
            }

            $properties[$key] = [
                'type' => is_int($settings[$key]) ? 'integer' : 'string',
                'title' => $title,
                'context' => ['view', 'edit'],
            ];
        }

        return [
            'namespace' => 'wp/v2',
            'methods' => $allow,
            'endpoints' => [
                [
                    'methods' => $allow,
                    'args' => (object) [],
                ],
            ],
            'schema' => [
                '$schema' => 'http://json-schema.org/draft-04/schema#',
                'title' => 'core/site',
                'type' => 'object',
                'properties' => $properties,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function siteSettings(): array
    {
        $path = $this->app->basePath('storage/settings.json');
        if (is_file($path)) {
            try {
                $decoded = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
                if (is_array($decoded)) {
                    return $decoded;
                }
            } catch (\JsonException) {
            }
        }

        return [];
    }
}
