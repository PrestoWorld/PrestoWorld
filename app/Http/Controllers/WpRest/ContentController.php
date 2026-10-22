<?php

declare(strict_types=1);

namespace App\Http\Controllers\WpRest;

use Cycle\Database\DatabaseInterface;
use App\Foundation\Application;
use Witals\Framework\Http\Request;
use Witals\Framework\Http\Response;

/**
 * `/wp-json/wp/v2/{posts|pages|blocks|navigation|media}` — content entities.
 *
 * Reads come from `pw_posts`. Writes are intentionally not offered for these
 * types yet: `canUser()` derives its answer from the `Allow` header the
 * OPTIONS probe returns, so advertising only `GET` keeps the editor's
 * affordances honest instead of failing after the user has already typed.
 *
 * Templates/template-parts (the entities the Site Editor actually edits)
 * live in TemplatesController with full CRUD.
 */
class ContentController extends RestController
{
    private const MAP = [
        'posts' => 'post',
        'pages' => 'page',
        'blocks' => 'wp_block',
        'navigation' => 'wp_navigation',
        'media' => 'attachment',
    ];

    public function __construct(Application $app, private DatabaseInterface $db)
    {
        parent::__construct($app);
    }

    /** GET /wp-json/wp/v2/{restBase} */
    public function index(Request $request, string $restBase): Response
    {
        $type = $this->typeFor($restBase);

        $query = $this->db->select('*')
            ->from($this->prefix() . 'posts')
            ->where('type', $type)
            ->where('status', '!=', 'auto-draft')
            ->where('status', '!=', 'trash');

        $search = trim($this->scalarQuery($request, 'search'));
        if ($search !== '') {
            $like = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $search) . '%';
            $query = $query->where('title', 'LIKE', $like);
        }

        $statuses = $this->statusList($request);
        if ($statuses !== []) {
            $query = $query->where('status', $statuses);
        }

        $query = $query->orderBy('created_at', 'DESC');

        $perPage = (int) $request->query('per_page', 10);
        if ($perPage > 0) {
            $page = max(1, (int) $request->query('page', 1));
            $query = $query->limit($perPage)->offset(($page - 1) * $perPage);
        }

        $items = array_map(fn (array $row): array => $this->record($row, $type), $query->fetchAll());

        return $this->collection($request, $items);
    }

    /**
     * WP `status` query arg: scalar (`status=publish`) or repeated
     * (`status[0]=publish&status[1]=draft`). `any` anywhere in the list
     * means "no extra restriction". Cycle compiles `where(col, array)`
     * as `col IN (...)`.
     *
     * @return list<string>
     */
    private function statusList(Request $request): array
    {
        $raw = $request->query('status', '');
        $values = is_array($raw) ? array_values($raw) : [$raw];

        $statuses = [];
        foreach ($values as $value) {
            if (!is_scalar($value)) {
                continue;
            }

            $value = (string) $value;
            if ($value === '' || $value === 'any') {
                return [];
            }

            $statuses[] = $value;
        }

        return array_values(array_unique($statuses));
    }

    /** GET /wp-json/wp/v2/{restBase}/{id} */
    public function show(Request $request, string $restBase, string $id): Response
    {
        $type = $this->typeFor($restBase);

        $row = $this->db->select('*')
            ->from($this->prefix() . 'posts')
            ->where('id', (int) $id)
            ->where('type', $type)
            ->run()
            ->fetch();

        if (!is_array($row) || $row === []) {
            return $this->error('rest_post_invalid', 'Invalid post ID.', 404);
        }

        return $this->respond($this->record($row, $type));
    }

    // ---------------------------------------------------------------------

    private function typeFor(string $restBase): string
    {
        return self::MAP[$restBase] ?? $restBase;
    }

    private function prefix(): string
    {
        return (string) (getenv('PW_TABLE_PREFIX') ?: 'pw_');
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    public function record(array $row, string $type): array
    {
        $id = (int) ($row['id'] ?? 0);
        $title = (string) ($row['title'] ?? '');
        $content = (string) ($row['content'] ?? '');
        $excerpt = (string) ($row['excerpt'] ?? '');
        $slug = (string) ($row['slug'] ?? '');
        $status = (string) ($row['status'] ?? 'draft');
        $date = (string) ($row['published_at'] ?? ($row['created_at'] ?? ''));

        $restBase = array_search($type, self::MAP, true) ?: 'posts';

        return [
            'id' => $id,
            'type' => $type,
            'status' => $status,
            'slug' => $slug,
            'date' => $date,
            'date_gmt' => $date,
            'modified' => (string) ($row['updated_at'] ?? $date),
            'modified_gmt' => (string) ($row['updated_at'] ?? $date),
            'link' => $this->siteUrl() . '/' . ltrim($slug, '/'),
            'title' => ['raw' => $title, 'rendered' => $title],
            'content' => ['raw' => $content, 'rendered' => $content],
            'excerpt' => ['raw' => $excerpt, 'rendered' => $excerpt],
            'author' => (int) ($row['author_id'] ?? 0),
            'parent' => (int) ($row['parent_id'] ?? 0),
            'menu_order' => (int) ($row['menu_order'] ?? 0),
            'template' => '',
            'featured_media' => 0,
            '_links' => [
                'self' => [
                    [
                        'href' => $this->restUrl($restBase . '/' . $id),
                        'targetHints' => ['allow' => ['GET']],
                    ],
                ],
                'collection' => [
                    [
                        'href' => $this->restUrl($restBase),
                    ],
                ],
            ],
        ];
    }
}
