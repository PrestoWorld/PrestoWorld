<?php

declare(strict_types=1);

namespace App\Http\Controllers\WpRest;

use App\Foundation\Application;
use App\Http\Rest\RestHeaders;
use PrestoWorld\WpRest\ThemeRepository;
use PrestoWorld\WpRest\WpDefaults;
use Witals\Framework\Http\Request;
use Witals\Framework\Http\Response;

/**
 * Base for the `/wp-json/wp/v2/*` WordPress-compatible REST surface.
 *
 * Provides the shared response envelope (WP uses `{code, message, data.status}`
 * for errors), the active-theme reader, and the record builders that turn
 * theme files into the JSON shapes `@wordpress/core-data` expects.
 */
abstract class RestController
{
    use RestHeaders;

    protected ?ThemeRepository $theme = null;

    public function __construct(protected Application $app)
    {
    }

    // ---------------------------------------------------------------------
    // Response helpers
    // ---------------------------------------------------------------------

    /**
     * Encode any payload as JSON. Unlike Response::json() this accepts
     * objects (needed where WP returns `{}` rather than `[]`).
     */
    protected function respond(mixed $data, int $status = 200, array $headers = []): Response
    {
        $headers += ['Content-Type' => 'application/json; charset=utf-8'];

        try {
            $body = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            $body = json_encode(['code' => 'rest_encode_failed', 'message' => $e->getMessage(), 'data' => ['status' => 500]]);
            $status = 500;
        }

        return Response::html($body, $status, $headers);
    }

    /**
     * WP_Error-shaped failure: `{code, message, data:{status}}`.
     */
    protected function error(string $code, string $message, int $status): Response
    {
        return $this->respond([
            'code' => $code,
            'message' => $message,
            'data' => ['status' => $status],
        ], $status);
    }

    protected function unauthorized(string $message = 'Sorry, you are not allowed to access this endpoint.'): Response
    {
        return $this->error('rest_not_logged_in', $message, 401);
    }

    protected function forbidden(string $message = 'Sorry, you are not allowed to do that.'): Response
    {
        return $this->error('rest_forbidden', $message, 403);
    }

    protected function notFound(string $message = 'Invalid route.'): Response
    {
        return $this->error('rest_no_route', $message, 404);
    }

    /**
     * Collection response with the pagination headers core-data reads.
     *
     * @param list<array<string, mixed>> $items
     */
    protected function collection(Request $request, array $items): Response
    {
        $page = max(1, (int) $request->query('page', 1));
        $perPage = (int) $request->query('per_page', 10);

        // Templates/parts/types/themes are consumed with per_page=-1 (no
        // pagination); return everything and report a single page.
        if ($perPage <= 0 || $perPage === -1) {
            return $this->respond($items, 200, [
                'X-WP-Total' => (string) count($items),
                'X-WP-TotalPages' => '1',
            ]);
        }

        $total = count($items);
        $slice = array_slice($items, ($page - 1) * $perPage, $perPage);

        return $this->respond(array_values($slice), 200, [
            'X-WP-Total' => (string) $total,
            'X-WP-TotalPages' => (string) max(1, (int) ceil($total / $perPage)),
        ]);
    }

    /**
     * Collection that always returns every record with pagination headers.
     *
     * The editor fetches templates, parts, patterns, variations and themes
     * without `per_page=-1` in several code paths, and WP serves those
     * in full; truncating at the default 10 would silently hide records.
     *
     * @param list<array<string, mixed>> $items
     */
    protected function fullCollection(array $items): Response
    {
        return $this->respond($items, 200, [
            'X-WP-Total' => (string) count($items),
            'X-WP-TotalPages' => '1',
        ]);
    }

    /** Force an empty JSON object `{}` (PHP encodes [] as `[]`). */
    protected function emptyObject(): Response
    {
        return $this->respond((object) []);
    }

    // ---------------------------------------------------------------------
    // Shared data sources
    // ---------------------------------------------------------------------

    protected function themeRepository(): ThemeRepository
    {
        if ($this->theme !== null) {
            return $this->theme;
        }

        $stylesheet = (string) $this->app->config('theme.active', 'timeless');

        $dir = $this->app->config('theme.path', null);
        if (!is_string($dir) || $dir === '') {
            $dir = $this->app->basePath('content/themes/' . $stylesheet);
        }

        $storageDir = $this->app->basePath('storage/contexts');

        $this->theme = new ThemeRepository($dir, $stylesheet, $storageDir);

        return $this->theme;
    }

    // ---------------------------------------------------------------------
    // Record builders
    // ---------------------------------------------------------------------

    /**
     * `GET /wp/v2/themes` item — the boot gate record.
     *
     * @return array<string, mixed>
     */
    protected function buildThemeRecord(): array
    {
        $repo = $this->themeRepository();
        $css = $repo->styleCss();
        $themeJson = $repo->themeJson();
        $stylesheet = $repo->stylesheet();

        $name = (string) ($css['name'] ?? $stylesheet);
        $description = (string) ($css['description'] ?? '');

        $defaultTypes = array_map(
            static fn (array $type, string $slug): array => $type + ['slug' => $slug],
            array_values(WpDefaults::defaultTemplateTypes()),
            array_keys(WpDefaults::defaultTemplateTypes()),
        );

        // theme_supports: mirror what the theme declares. editor-styles is
        // what the site editor reads first; post-thumbnails/formats come from
        // functions.php add_theme_support() calls.
        $themeSupports = [
            'editor-styles' => true,
            'responsive-embeds' => true,
            'post-thumbnails' => true,
            'automatic-feed-links' => true,
            'menus' => true,
            'html5' => ['search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script'],
            'title-tag' => true,
            'align-wide' => true,
            'wp-block-styles' => true,
            'disable-custom-colors' => false,
            'disable-custom-font-sizes' => false,
            'appearance-tools' => true,
        ];

        if (preg_match("/add_theme_support\\(\\s*'post-formats'\\s*,\\s*\\[(.*?)\\]/s", (string) @file_get_contents($repo->themeDir() . '/functions.php'), $m) === 1) {
            $formats = [];
            if (preg_match_all("/'([a-z-]+)'/", $m[1], $mm) === 1) {
                $formats = $mm[1];
            }
            if ($formats !== []) {
                $themeSupports['post-formats'] = $formats;
            }
        }

        return [
            'id' => $stylesheet,
            'name' => ['raw' => $name, 'rendered' => $name],
            'description' => ['raw' => $description, 'rendered' => $description],
            'stylesheet' => $stylesheet,
            'theme_uri' => (string) ($css['theme_uri'] ?? ''),
            'author' => ['raw' => (string) ($css['author'] ?? ''), 'rendered' => (string) ($css['author'] ?? '')],
            'author_uri' => (string) ($css['author_uri'] ?? ''),
            'version' => (string) ($css['version'] ?? '1.0.0'),
            'requires_wp' => (string) ($css['requires_wp'] ?? ''),
            'requires_php' => (string) ($css['requires_php'] ?? ''),
            'license' => (string) ($css['license'] ?? ''),
            'license_uri' => (string) ($css['license_uri'] ?? ''),
            'tags' => array_values($css['tags'] ?? []),
            'screenshot' => is_file($repo->themeDir() . '/screenshot.png')
                ? $this->contentUrl('screenshot.png')
                : '',
            'status' => 'active',
            'is_block_theme' => true,
            'theme_supports' => $themeSupports,
            'default_template_types' => $defaultTypes,
            'default_template_part_areas' => WpDefaults::defaultTemplatePartAreas(),
            'custom_templates' => $repo->customTemplates(),
            'theme_json_version' => $themeJson['version'] ?? 3,
            '_links' => [
                'self' => [
                    [
                        'href' => $this->restUrl('themes/' . rawurlencode($stylesheet)),
                        'targetHints' => ['allow' => ['GET']],
                    ],
                ],
                'wp:user-global-styles' => [
                    [
                        // Numeric id so core-data can extract it with /\/(\d+)/.
                        'href' => $this->restUrl('global-styles/1'),
                    ],
                ],
            ],
        ];
    }

    /**
     * `wp_template` / `wp_template_part` item.
     *
     * @param array<string, string> $defaultTypes
     * @param array<string, array<string, mixed>> $partsMeta
     * @return array<string, mixed>
     */
    protected function buildTemplateRecord(
        string $slug,
        string $content,
        string $type,
        array $defaultTypes,
        array $partsMeta = [],
        ?bool $hasOverride = null,
    ): array {
        $repo = $this->themeRepository();
        $stylesheet = $repo->stylesheet();

        $isPart = $type === 'wp_template_part';

        // has_theme_file: true when the theme ships a file for this slug.
        $hasThemeFile = $isPart
            ? $this->partExistsInTheme($slug)
            : $this->templateExistsInTheme($slug);

        $override = $hasOverride ?? ($isPart ? $repo->hasPartOverride($slug) : $repo->hasTemplateOverride($slug));

        $default = $defaultTypes[$slug] ?? null;
        $customMeta = null;
        if (!$isPart) {
            foreach ($repo->customTemplates() as $entry) {
                if (($entry['name'] ?? '') === $slug) {
                    $customMeta = $entry;
                    break;
                }
            }
        }

        $title = is_array($customMeta['title'] ?? null)
            ? (string) ($customMeta['title']['raw'] ?? $customMeta['title']['rendered'] ?? '')
            : (string) ($customMeta['title'] ?? '');

        if ($title === '') {
            $title = (string) ($default['title'] ?? ($partsMeta[$slug]['title'] ?? ucfirst($slug)));
        }

        $description = is_array($default['description'] ?? null)
            ? (string) ($default['description']['raw'] ?? '')
            : (string) ($default['description'] ?? '');

        if ($isPart) {
            $area = (string) ($partsMeta[$slug]['area'] ?? 'uncategorized');
            if ($area === '') {
                $area = 'uncategorized';
            }
        } else {
            $area = null;
        }

        $source = $override ? 'custom' : 'theme';

        $record = [
            'id' => $stylesheet . '//' . $slug,
            'slug' => $slug,
            'theme' => $stylesheet,
            'type' => $type,
            'title' => ['raw' => $title, 'rendered' => $title],
            'content' => ['raw' => $content, 'rendered' => $content],
            'description' => $description,
            'source' => $source,
            'origin' => $override ? 'site' : 'theme',
            'original_source' => 'theme',
            'status' => 'publish',
            'wp_id' => 0,
            'author' => 0,
            'author_text' => '',
            'has_theme_file' => $hasThemeFile,
            'is_custom' => $isPart ? true : !isset($defaultTypes[$slug]),
            'plugin' => null,
            'modified' => null,
            '_links' => [
                'self' => [
                    [
                        'href' => $this->restUrl(
                            ($isPart ? 'template-parts/' : 'templates/') . rawurlencode($stylesheet . '//' . $slug)
                        ),
                        'targetHints' => ['allow' => ['GET', 'POST', 'PUT', 'DELETE']],
                    ],
                ],
                'collection' => [
                    [
                        'href' => $this->restUrl($isPart ? 'template-parts' : 'templates'),
                    ],
                ],
            ],
        ];

        if ($isPart) {
            $record['area'] = $area;
            $record['area_title'] = $this->areaLabel($area, $partsMeta);
        }

        return $record;
    }

    /**
     * `GET /wp/v2/global-styles/{id}` (user record) — theme defaults unless
     * the site has an override.
     *
     * @return array<string, mixed>
     */
    protected function buildUserGlobalStyles(): array
    {
        $repo = $this->themeRepository();
        $overridePath = $this->app->basePath('storage/global-styles.json');
        $data = [];
        if (is_file($overridePath)) {
            try {
                $decoded = json_decode((string) file_get_contents($overridePath), true, 512, JSON_THROW_ON_ERROR);
                if (is_array($decoded)) {
                    $data = $decoded;
                }
            } catch (\JsonException) {
                $data = [];
            }
        }

        if ($data === []) {
            $themeJson = $repo->themeJson();
            $data = [
                'version' => $themeJson['version'] ?? 3,
                'settings' => $themeJson['settings'] ?? [],
                'styles' => $themeJson['styles'] ?? [],
            ];
        }

        $data += [
            'id' => 1,
            'title' => '',
            'isGlobalStylesUserThemeJSON' => true,
            '_links' => [
                'self' => [
                    [
                        'href' => $this->restUrl('global-styles/1'),
                        'targetHints' => ['allow' => ['GET', 'POST', 'PUT']],
                    ],
                ],
            ],
        ];

        return $data;
    }

    // ---------------------------------------------------------------------
    // Internals
    // ---------------------------------------------------------------------

    private function templateExistsInTheme(string $slug): bool
    {
        return is_file($this->themeRepository()->themeDir() . '/templates/' . $slug . '.html');
    }

    private function partExistsInTheme(string $slug): bool
    {
        return is_file($this->themeRepository()->themeDir() . '/parts/' . $slug . '.html');
    }

    /**
     * @param array<string, array<string, mixed>> $partsMeta
     */
    private function areaLabel(string $area, array $partsMeta): string
    {
        foreach (WpDefaults::defaultTemplatePartAreas() as $entry) {
            if ($entry['area'] === $area) {
                return $entry['label'];
            }
        }

        return $area;
    }

    /**
     * Absolute URL inside the REST namespace (path after `/wp-json/`).
     */
    protected function restUrl(string $path = ''): string
    {
        $base = $this->siteUrl() . '/wp-json/wp/v2/';

        return $path === '' ? rtrim($base, '/') : $base . ltrim($path, '/');
    }

    protected function siteUrl(): string
    {
        $https = ((($_SERVER['HTTPS'] ?? '') !== '') && $_SERVER['HTTPS'] !== 'off')
            || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');

        $host = (string) ($_SERVER['HTTP_HOST'] ?? '');
        if ($host === '') {
            return '';
        }

        return ($https ? 'https' : 'http') . '://' . $host;
    }

    protected function contentUrl(string $relative): string
    {
        return $this->siteUrl() . '/content/themes/' . $this->themeRepository()->stylesheet() . '/' . ltrim($relative, '/');
    }

    /**
     * `X-HTTP-Method-Override` — api-fetch turns PUT/DELETE into POST plus
     * this header (httpV1 middleware), so the `{id}` routes branch on it.
     */
    protected function methodOverride(Request $request): string
    {
        return strtoupper($this->headerValue($request, 'X-HTTP-Method-Override'));
    }

    /**
     * Split a WP entity id (`{stylesheet}//{slug}`) into its parts.
     *
     * The router hands over the still-percent-encoded path segment, so the
     * id is decoded here. A bare slug (no `//`) is attributed to the active
     * theme.
     *
     * @return array{theme: string, slug: string}
     */
    protected function splitId(string $id): array
    {
        $decoded = rawurldecode($id);

        if (str_contains($decoded, '//')) {
            [$theme, $slug] = explode('//', $decoded, 2);

            return ['theme' => $theme, 'slug' => $slug];
        }

        return ['theme' => $this->themeRepository()->stylesheet(), 'slug' => $decoded];
    }

    /**
     * Read a string payload field that may arrive either as a plain string or
     * as a `{raw, rendered}` object (WP accepts both shapes).
     */
    protected function payloadString(mixed $value, string $fallbackKey = ''): string
    {
        if (is_string($value)) {
            return $value;
        }

        if (is_array($value)) {
            if (isset($value['raw']) && is_string($value['raw'])) {
                return $value['raw'];
            }
            if (isset($value['rendered']) && is_string($value['rendered'])) {
                return $value['rendered'];
            }
        }

        return '';
    }

    /**
     * Extract the block-comment HTML from a save payload.
     */
    protected function payloadContent(mixed $content): string
    {
        return $this->payloadString($content);
    }

    /**
     * Slug for a new record: explicit slug wins, otherwise derive from title
     * or from `wp:slug`-style metadata.
     */
    protected function deriveSlug(array $payload, string $fallback = ''): string
    {
        $slug = $this->payloadString($payload['slug'] ?? '');
        if ($slug === '') {
            $slug = $this->payloadString($payload['title'] ?? '');
        }
        if ($slug === '') {
            $slug = $fallback;
        }

        $slug = strtolower(trim($slug));
        $slug = (string) preg_replace('/[^a-z0-9\-]+/', '-', $slug);
        $slug = (string) preg_replace('/-+/', '-', $slug);
        $slug = trim($slug, '-');

        if ($slug === '') {
            $slug = 'wp-' . substr((string) bin2hex(random_bytes(4)), 0, 8);
        }

        return $slug;
    }
}
