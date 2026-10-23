<?php

declare(strict_types=1);

namespace App\Http\Controllers\WpRest;

use Cycle\Database\DatabaseInterface;
use App\Foundation\Application;
use Witals\Framework\Contracts\Auth\AuthContextInterface;
use Witals\Framework\Http\Request;
use Witals\Framework\Http\Response;

/**
 * `/wp-json/wp-block-editor/v1/*` — editor bootstrap data.
 *
 * `core-data` resolves these with **no error handling**: a 404 leaves the
 * `editorSettings`/`editorAssets` selectors unresolved and surfaces as an
 * unhandled rejection in the console, so both endpoints answer with a usable
 * object instead.
 */
class BlockEditorController extends RestController
{
    public function __construct(
        Application $app,
        private DatabaseInterface $db,
        protected ?AuthContextInterface $auth = null,
    ) {
        parent::__construct($app);
    }

    /** GET /wp-json/wp-block-editor/v1/settings */
    public function settings(Request $request): Response
    {
        $settingsJson = $this->themeRepository()->themeJson();

        return $this->respond([
            'canPublish' => true,
            'storageSupport' => true,
            'codeEditingEnabled' => true,
            'richEditingEnabled' => true,
            'autosaveInterval' => 60,
            'availableTemplates' => (object) [],
            'allowedBlockTypes' => true,
            'allowedMimeTypes' => (object) [],
            'maxUploadFileSize' => 0,
            'isRTL' => false,
            'locale' => 'en_US',
            'userLocale' => 'en_US',
            'postsPerPage' => 10,
            'imageDefaultSize' => 'large',
            'imageSizes' => [],
            'imageDirname' => 'uploads',
            'fluidLayoutSizeBreakpoints' => [],
            'siteIcon' => 0,
            'siteLogo' => 0,
            'siteTitle' => $this->siteTitle(),
            'hasReusableBlocks' => true,
            'postLock' => [
                'enabled' => false,
                'interval' => 150,
                'refreshInterval' => 10,
            ],
            'blockCategories' => $this->blockCategories(),
            'defaultTemplateTypes' => \PrestoWorld\WpRest\WpDefaults::defaultTemplateTypes(),
            'defaultTemplatePartAreas' => \PrestoWorld\WpRest\WpDefaults::defaultTemplatePartAreas(),
            'styles' => $settingsJson['styles'] ?? (object) [],
        ]);
    }

    /** GET /wp-json/wp-block-editor/v1/assets */
    public function assets(Request $request): Response
    {
        return $this->respond((object) []);
    }

    /** GET /wp-json/wp-block-editor/v1/export  (site export — not boot data) */
    public function export(Request $request): Response
    {
        return $this->error('rest_cannot_export', 'Site export is not available on this installation.', 501);
    }

    /**
     * GET /wp-json/wp-block-editor/v1/navigation-fallback?_embed=true
     *
     * Mirrors `WP_REST_Navigation_Fallback_Controller`: return the newest
     * published `wp_navigation` post, creating the default fallback
     * (`<!-- wp:page-list /-->`, title "Navigation") when none exists —
     * WordPress creates it on this same GET. `core-data` resolves the path
     * with no error handling, so an unanswered route (404) surfaces as an
     * unhandled rejection in the console.
     */
    public function navigationFallback(Request $request): Response
    {
        $token = $this->auth?->getToken();
        if ($token === null) {
            return $this->unauthorized();
        }

        $row = $this->latestNavigation() ?? $this->createDefaultNavigation($token->getPayload());

        if ($row === null) {
            return $this->error('no_fallback_menu', 'No fallback menu found.', 404);
        }

        $id = (int) ($row['id'] ?? 0);
        $body = [
            'id' => $id,
            '_links' => [
                'self' => [
                    [
                        'href' => $this->restUrl('navigation/' . $id),
                        'embeddable' => true,
                    ],
                ],
            ],
        ];

        // `_embed=true` → `_embedded.self` holds the full record; the resolver
        // receives it into the store and invalidates stale navigation queries.
        if ($request->query('_embed', null) !== null) {
            $record = $this->app->make(ContentController::class)->record($row, 'wp_navigation');
            $body['_embedded'] = ['self' => [$record]];
        }

        return $this->respond($body);
    }

    private function latestNavigation(): ?array
    {
        $row = $this->db->select('*')
            ->from($this->prefix() . 'posts')
            ->where('type', 'wp_navigation')
            ->where('status', 'publish')
            ->orderBy('created_at', 'DESC')
            ->limit(1)
            ->run()
            ->fetch();

        return (is_array($row) && $row !== []) ? $row : null;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function createDefaultNavigation(array $payload): ?array
    {
        // `slug` carries a global unique index in pw_posts — WordPress allows
        // per-type slugs, so fall back to a variant when "navigation" is taken.
        $slug = 'navigation';
        for ($i = 1; $i <= 25 && $this->slugExists($slug); $i++) {
            $slug = 'navigation-' . $i;
        }

        $now = date('Y-m-d H:i:s');
        $author = (int) ($payload['sub'] ?? $payload['user_id'] ?? $payload['id'] ?? 0);

        try {
            $this->db->insert($this->prefix() . 'posts')->values([
                'title' => 'Navigation',
                'slug' => $slug,
                'content' => '<!-- wp:page-list /-->',
                'excerpt' => '',
                'status' => 'publish',
                'type' => 'wp_navigation',
                'author_id' => $author > 0 ? $author : 1,
                'menu_order' => 0,
                'created_at' => $now,
                'updated_at' => $now,
                'published_at' => $now,
            ])->run();

            $insertId = $this->db->getDriver()->lastInsertID();
            $id = is_scalar($insertId) ? (int) $insertId : 0;

            if ($id <= 0) {
                return null;
            }

            \PrestoWorld\Core\Cache\CacheInvalidation::onContentChange('navigation-create');

            return $this->latestNavigationById($id);
        } catch (\Throwable) {
            return null;
        }
    }

    private function latestNavigationById(int $id): ?array
    {
        $row = $this->db->select('*')
            ->from($this->prefix() . 'posts')
            ->where('id', $id)
            ->where('type', 'wp_navigation')
            ->limit(1)
            ->run()
            ->fetch();

        return (is_array($row) && $row !== []) ? $row : null;
    }

    private function slugExists(string $slug): bool
    {
        $row = $this->db->select('id')
            ->from($this->prefix() . 'posts')
            ->where('slug', $slug)
            ->limit(1)
            ->run()
            ->fetch();

        return is_array($row) && $row !== [];
    }

    private function prefix(): string
    {
        return (string) (getenv('PW_TABLE_PREFIX') ?: 'pw_');
    }

    /**
     * @return list<array{name: string, title: string, icon: string}>
     */
    private function blockCategories(): array
    {
        $names = [
            ['text', 'Text', 'text'],
            ['media', 'Media', 'format-image'],
            ['design', 'Design', 'layout'],
            ['widgets', 'Widgets', 'widgets'],
            ['theme', 'Theme', 'archive'],
            ['embed', 'Embeds', 'embed-generic'],
        ];

        return array_map(
            static fn (array $entry): array => ['name' => $entry[0], 'title' => $entry[1], 'icon' => $entry[2]],
            $names,
        );
    }

    private function siteTitle(): string
    {
        $path = $this->app->basePath('storage/settings.json');
        if (is_file($path)) {
            try {
                $data = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
                if (is_array($data)) {
                    $title = (string) ($data['title'] ?? ($data['blogname'] ?? ''));
                    if ($title !== '') {
                        return $title;
                    }
                }
            } catch (\JsonException) {
            }
        }

        return (string) $this->app->config('app.name', '');
    }
}
