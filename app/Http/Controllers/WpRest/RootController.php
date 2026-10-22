<?php

declare(strict_types=1);

namespace App\Http\Controllers\WpRest;

use PrestoWorld\Gutenberg\ViewConfig;
use PrestoWorld\WpRest\WpDefaults;
use Witals\Framework\Contracts\Auth\AuthContextInterface;
use Witals\Framework\Http\Request;
use Witals\Framework\Http\Response;

/**
 * Boot data consumed before the Site Editor renders:
 *
 *   GET /wp-json/                     → core/__unstableBase (site settings)
 *   GET /wp-json/wp/v2/settings       → site options
 *   POST /wp-json/wp/v2/settings      → save site options
 *   GET /wp-json/wp/v2/users/me       → current user (capability gating)
 *   GET /wp-json/wp/v2/view-config    → DataViews form/layout config
 */
class RootController extends RestController
{
    /** GET /wp-json/ — the apiFetch root (`__unstableBase` entity). */
    public function index(Request $request): Response
    {
        $site = $this->siteSettings();

        $record = [
            'name' => $site['title'],
            'description' => $site['description'],
            'url' => $this->siteUrl(),
            'home' => $this->siteUrl(),
            'gmt_offset' => $site['gmt_offset'],
            'timezone_string' => $site['timezone_string'],
            'site_icon' => 0,
            'site_icon_url' => '',
            'site_logo' => 0,
            'show_on_front' => $site['show_on_front'],
            'page_on_front' => $site['page_on_front'],
            'page_for_posts' => $site['page_for_posts'],
            'image_sizes' => (object) [],
            'image_size_threshold' => 0,
            'image_strip_meta' => true,
            'image_max_bit_depth' => 8,
            'namespaces' => ['wp/v2'],
            'authentication' => (object) [],
            'routes' => (object) [],
            '_links' => (object) [],
        ];

        // `_fields` is a plain allow-list; ignoring it is safe (core-data
        // only reads the keys it asked for).
        return $this->respond($record);
    }

    /** GET /wp-json/wp/v2/settings */
    public function settings(): Response
    {
        return $this->respond($this->siteSettings());
    }

    /** POST /wp-json/wp/v2/settings (PUT arrives as POST + method override). */
    public function saveSettings(Request $request): Response
    {
        $payload = $request->getJson();
        if (!is_array($payload) || $payload === []) {
            return $this->error('rest_invalid_param', 'Invalid settings payload.', 400);
        }

        $stored = $this->loadStoredSettings();
        foreach ($payload as $key => $value) {
            if (is_string($key) && $key !== '_links') {
                $stored[$key] = $value;
            }
        }

        $path = $this->app->basePath('storage/settings.json');
        $dir = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents(
            $path,
            json_encode($stored, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
        );

        return $this->respond($this->siteSettings());
    }

    /** GET /wp-json/wp/v2/users/me */
    public function me(): Response
    {
        return $this->respond($this->meRecord());
    }

    /**
     * GET /wp-json/wp/v2/users — author lookups in the editor only ever need
     * the signed-in user on this installation.
     */
    public function users(Request $request): Response
    {
        return $this->collection($request, [$this->meRecord()]);
    }

    /**
     * @return array<string, mixed>
     */
    private function meRecord(): array
    {
        $auth = $this->resolveAuth();

        $id = 0;
        $name = '';
        $email = '';
        $role = '';
        $roles = [];

        if ($auth !== null && $auth->getToken() !== null) {
            $payload = $auth->getToken()->getPayload();
            $id = (int) ($payload['sub'] ?? $payload['user_id'] ?? $payload['id'] ?? 0);
            $name = (string) ($payload['name'] ?? $payload['display_name'] ?? $payload['username'] ?? '');
            $email = (string) ($payload['email'] ?? '');
            $role = strtolower((string) ($payload['role'] ?? ($payload['roles'][0] ?? '')));
            $roles = is_array($payload['roles'] ?? null) ? $payload['roles'] : ($role !== '' ? [$role] : []);
        }

        $caps = $this->capabilities($role);

        return [
            'id' => $id,
            'name' => $name !== '' ? $name : ($email !== '' ? $email : 'Editor'),
            'slug' => $email,
            'url' => '',
            'description' => '',
            'link' => $this->siteUrl(),
            'avatar_urls' => (object) [],
            'capabilities' => $caps,
            'extra_capabilities' => (object) [],
            'roles' => $roles,
            'role' => $role,
            'email' => $email,
            'locale' => 'en',
            'isSuperAdmin' => $role === 'super_admin',
            '_links' => (object) [],
        ];
    }

    /**
     * GET /wp-json/wp/v2/view-config?kind=&name=
     *
     * DataViews/DataForm read `default_view`, `default_layouts`, `view_list`
     * and `form` from this record. The shape is built by the GPL package's
     * ViewConfig port of Gutenberg's view-config API; the controller only
     * supplies the runtime data those callbacks read from WordPress state.
     */
    public function viewConfig(Request $request): Response
    {
        $kind = (string) $request->query('kind', '');
        $name = (string) $request->query('name', '');

        $config = (new ViewConfig())->for($kind, $name, [
            'allItemsLabel' => $this->allItemsLabel($kind, $name),
            'patternCategories' => PatternsController::categoryMap($this->themeRepository()->patterns()),
            'partAreas' => WpDefaults::defaultTemplatePartAreas(),
        ]);

        return $this->respond([
            'kind' => $kind,
            'name' => $name,
            'version' => 0,
            ...$config,
        ]);
    }

    /**
     * Sidebar title of the base `all` view.
     *
     * Upstream reads the post type's `all_items` label, which the label set
     * does not mirror; every post type we register uses its `name` label for
     * that entry (WP does the same: Posts, Pages, ...).
     *
     * @return string
     */
    private function allItemsLabel(string $kind, string $name): string
    {
        if ($kind !== 'postType') {
            return 'All items';
        }

        return (string) (WpDefaults::postTypes()[$name]['labels']['name'] ?? 'All items');
    }

    // ---------------------------------------------------------------------

    /**
     * Site options merged from defaults + `storage/settings.json`.
     *
     * @return array<string, mixed>
     */
    private function siteSettings(): array
    {
        return $this->loadStoredSettings() + [
            'title' => (string) $this->app->config('theme.default_title', 'PrestoWorld'),
            'description' => '',
            'url' => $this->siteUrl(),
            'email' => '',
            'timezone' => 'UTC',
            'gmt_offset' => '0',
            'timezone_string' => '',
            'date_format' => 'F j, Y',
            'time_format' => 'g:i a',
            'language' => 'en',
            'start_of_week' => 1,
            'use_smilies' => true,
            'use_balanceTags' => false,
            'require_name_email' => true,
            'comment_moderation' => false,
            'show_avatars' => true,
            'default_category' => 1,
            'default_post_format' => '0',
            'default_comment_status' => 'open',
            'default_ping_status' => 'open',
            'posts_per_page' => 10,
            'show_on_front' => 'posts',
            'page_on_front' => 0,
            'page_for_posts' => 0,
            'page_comments' => false,
            'comments_per_page' => 50,
            'thread_comments' => true,
            'thread_comments_depth' => 5,
            'default_pingback_flag' => true,
            'ping_sites' => '',
            'blog_public' => 1,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function loadStoredSettings(): array
    {
        $path = $this->app->basePath('storage/settings.json');
        if (!is_file($path)) {
            return [];
        }

        try {
            $decoded = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);

            return is_array($decoded) ? $decoded : [];
        } catch (\JsonException) {
            return [];
        }
    }

    private function resolveAuth(): ?AuthContextInterface
    {
        try {
            $auth = $this->app->make(AuthContextInterface::class);

            return $auth instanceof AuthContextInterface ? $auth : null;
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Capability map used by `canUser()` probes and save/delete affordances.
     *
     * @return array<string, bool>
     */
    private function capabilities(string $role): array
    {
        $caps = [
            'read' => true,
            'edit_posts' => true,
            'edit_others_posts' => true,
            'publish_posts' => true,
            'delete_posts' => true,
            'delete_others_posts' => true,
            'edit_published_posts' => true,
            'edit_private_posts' => true,
            'edit_pages' => true,
            'edit_others_pages' => true,
            'publish_pages' => true,
            'delete_pages' => true,
            'delete_others_pages' => true,
            'manage_categories' => true,
            'manage_links' => true,
            'manage_options' => true,
            'moderate_comments' => true,
            'unfiltered_html' => true,
            'upload_files' => true,
            'edit_theme_options' => true,
            'switch_themes' => true,
            'install_plugins' => false,
            'delete_plugins' => false,
            'update_plugins' => false,
            'activate_plugins' => false,
            'create_users' => true,
            'list_users' => true,
            'edit_users' => true,
            'delete_users' => true,
        ];

        if (!in_array($role, ['admin', 'super_admin', 'administrator', 'editor'], true)) {
            return array_map(static fn (): bool => false, $caps) + ['read' => true];
        }

        return $caps;
    }
}
