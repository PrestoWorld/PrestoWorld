<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use Cycle\Database\DatabaseInterface;
use Witals\Framework\Http\Request;
use Witals\Framework\Http\Response;

class PostsController
{
    use AdminControllerHelperTrait;

    public function __construct(
        protected DatabaseInterface $db,
    ) {
        $this->initHelpers($db);
    }

    public function posts(): Response
    {
        $query = $this->db->select('*')
            ->from($this->prefix . 'posts')
            ->where('status', '!=', 'auto-draft');

        $type = $_GET['type'] ?? null;
        if ($type !== null) {
            $query = $query->where('post_type', $type);
        } elseif ($this->isWordPress()) {
            $query = $query->where('post_type', 'post');
        }

        $rows = $query->orderBy('created_at', 'DESC')->fetchAll();

        $posts = array_map(fn(array $row) => [
            'id' => (int) ($row['ID'] ?? $row['id']),
            'title' => $row['post_title'] ?? $row['title'] ?? '',
            'author' => $this->resolveAuthor((int) ($row['post_author'] ?? $row['author_id'] ?? 0)),
            'category' => $this->resolveCategory((int) ($row['ID'] ?? $row['id'])),
            'status' => $this->mapStatus($row['post_status'] ?? $row['status'] ?? 'draft'),
            'date' => $this->formatDate($row['post_date'] ?? $row['created_at'] ?? ''),
            'commentsCount' => (int) ($row['comment_count'] ?? $this->extractMeta($row, 'comments_count', 0)),
        ], $rows);

        return Response::json($posts);
    }

    protected function resolveAuthor(int $authorId): string
    {
        if ($authorId < 1) return 'admin';
        try {
            $user = $this->db->select('user_nicename, display_name')
                ->from($this->prefix . 'users')
                ->where('ID', $authorId)
                ->limit(1)
                ->fetch();
            return $user['display_name'] ?? $user['user_nicename'] ?? 'admin';
        } catch (\Throwable) {
            return 'admin';
        }
    }

    protected function resolveCategory(int $postId): string
    {
        try {
            $term = $this->db->select('t.name')
                ->from($this->prefix . 'term_relationships as tr')
                ->innerJoin($this->prefix . 'term_taxonomy', 'tt')->on('tt.term_taxonomy_id', 'tr.term_taxonomy_id')
                ->innerJoin($this->prefix . 'terms', 't')->on('t.term_id', 'tt.term_id')
                ->where('tr.object_id', $postId)
                ->where('tt.taxonomy', 'category')
                ->limit(1)
                ->fetch();

            return $term['name'] ?? 'Uncategorized';
        } catch (\Throwable) {
            return 'Uncategorized';
        }
    }

    public function savePost(Request $request): never
    {
        $title = $this->postString($request, 'title');
        $slugInput = $this->postString($request, 'slug');
        $slug = $slugInput !== '' ? $slugInput : $this->slugify($title);
        $status = $this->resolveStatus($request);

        $meta = $this->buildMeta($request);

        $data = [
            'post_type' => $this->postString($request, 'post_type', 'post'),
            'title' => $title,
            'slug' => $slug,
            'status' => $status,
            'author_id' => $this->postInt($request, 'author_id', 1),
            'created_at' => $this->resolvePublishDate($request),
            'compact_meta' => json_encode($meta),
        ];

        $this->db->insert($this->prefix . 'posts')->values($data)->run();
        $insertId = $this->db->getDriver()->lastInsertID();
        $postId = is_scalar($insertId) ? (int) $insertId : 0;

        if ($postId > 0) {
            $this->saveCategories($request, $postId);
            $this->saveTags($request, $postId);
        }

        header('Location: /wp-admin/post.php?post=' . $postId . '&saved=1');
        exit;
    }

    public function updatePost(Request $request): never
    {
        $postId = $this->postInt($request, 'post_id');

        if ($request->query('trash') === '1' && $postId > 0) {
            $this->db->update($this->prefix . 'posts', ['status' => 'trash'], ['id' => $postId])->run();
            header('Location: /wp-admin/edit.php');
            exit;
        }

        if ($postId < 1) {
            header('Location: /wp-admin/edit.php');
            exit;
        }

        $title = $this->postString($request, 'title');
        $slugInput = $this->postString($request, 'slug');
        $slug = $slugInput !== '' ? $slugInput : $this->slugify($title);
        $status = $this->resolveStatus($request);

        $meta = $this->buildMeta($request);
        $existingMeta = $this->getExistingMeta($postId);
        $meta = array_merge($existingMeta, $meta);

        $data = [
            'title' => $title,
            'slug' => $slug,
            'status' => $status,
            'updated_at' => date('Y-m-d H:i:s'),
            'compact_meta' => json_encode($meta),
        ];

        $publishDate = $this->resolvePublishDate($request);
        if ($publishDate !== null) {
            $data['created_at'] = $publishDate;
        }

        $this->db->update($this->prefix . 'posts', $data, ['id' => $postId])->run();

        $this->saveCategories($request, $postId);
        $this->saveTags($request, $postId);

        header('Location: /wp-admin/post.php?post=' . $postId . '&updated=1');
        exit;
    }

    private function resolveStatus(Request $request): string
    {
        $visibility = $this->postString($request, 'visibility', 'public');
        $status = $this->postString($request, 'status', 'draft');

        if ($visibility === 'private') {
            return 'private';
        }

        $submitSave = $request->post('submit_save', null);
        if ($submitSave !== null && $status === 'publish') {
            return 'draft';
        }

        return $status;
    }

    /** @return array<string, mixed> */
    private function buildMeta(Request $request): array
    {
        return [
            'content' => $this->postString($request, 'content'),
            'excerpt' => $this->postString($request, 'excerpt'),
            'password' => $this->postString($request, 'password'),
            'featured_image' => $this->postString($request, 'featured_image'),
            'visibility' => $this->postString($request, 'visibility', 'public'),
        ];
    }

    /** @return array<string, mixed> */
    private function getExistingMeta(int $postId): array
    {
        try {
            /** @var array<string, mixed>|false $row */
            $row = $this->db->select('compact_meta')
                ->from($this->prefix . 'posts')
                ->where('id', $postId)
                ->run()
                ->fetch();
            if (is_array($row) && isset($row['compact_meta']) && is_string($row['compact_meta'])) {
                $decoded = json_decode($row['compact_meta'], true);
                return is_array($decoded) ? $decoded : [];
            }
        } catch (\Throwable) {}
        return [];
    }

    private function resolvePublishDate(Request $request): string
    {
        $dateStr = $this->postString($request, 'publish_date');
        if ($dateStr === '') {
            return date('Y-m-d H:i:s');
        }
        $ts = strtotime($dateStr);
        return $ts !== false ? date('Y-m-d H:i:s', $ts) : date('Y-m-d H:i:s');
    }

    private function saveCategories(Request $request, int $postId): void
    {
        $newCatName = $this->postString($request, 'new_category');
        if ($newCatName !== '') {
            $slug = $this->slugify($newCatName);
            $existing = $this->db->select('id')->from($this->prefix . 'terms')
                ->where('taxonomy', 'category')->where('slug', $slug)->run()->fetch();
            if (!$existing) {
                $this->db->insert($this->prefix . 'terms')->values([
                    'taxonomy' => 'category',
                    'name' => $newCatName,
                    'slug' => $slug,
                    'count' => 0,
                ])->run();
            }
        }

        $catIds = $request->post('categories', []);
        if (!is_array($catIds)) {
            return;
        }

        $this->db->delete($this->prefix . 'term_relationships', ['object_id' => $postId])->run();

        foreach ($catIds as $cid) {
            $termId = is_scalar($cid) ? (int) $cid : 0;
            if ($termId > 0) {
                $this->db->insert($this->prefix . 'term_relationships')->values([
                    'object_id' => $postId,
                    'term_id' => $termId,
                ])->run();
            }
        }
    }

    private function saveTags(Request $request, int $postId): void
    {
        $tagStr = $this->postString($request, 'tags');
        if ($tagStr === '') {
            return;
        }

        $tagNames = array_filter(array_map('trim', explode(',', $tagStr)));

        $this->db->delete($this->prefix . 'term_relationships', ['object_id' => $postId])->run();

        foreach ($tagNames as $name) {
            if ($name === '') {
                continue;
            }
            $slug = $this->slugify($name);
            $existing = $this->db->select('id')->from($this->prefix . 'terms')
                ->where('taxonomy', 'post_tag')->where('slug', $slug)->run()->fetch();
            if ($existing) {
                $termId = (int) ($existing['id'] ?? 0);
            } else {
                $this->db->insert($this->prefix . 'terms')->values([
                    'taxonomy' => 'post_tag',
                    'name' => $name,
                    'slug' => $slug,
                    'count' => 0,
                ])->run();
                $insertId = $this->db->getDriver()->lastInsertID();
                $termId = is_scalar($insertId) ? (int) $insertId : 0;
            }

            if ($termId > 0) {
                $this->db->insert($this->prefix . 'term_relationships')->values([
                    'object_id' => $postId,
                    'term_id' => $termId,
                ])->run();
            }
        }
    }

    private function postString(Request $request, string $key, string $default = ''): string
    {
        $value = $request->post($key, $default);
        return is_string($value) ? $value : $default;
    }

    private function postInt(Request $request, string $key, int $default = 0): int
    {
        $value = $request->post($key, $default);
        return is_scalar($value) ? (int) $value : $default;
    }

    private function slugify(string $text): string
    {
        $text = (string) preg_replace('/[^\p{L}\p{N}\s-]/u', '', $text);
        $text = (string) preg_replace('/[\s-]+/', '-', trim($text));
        $text = trim($text, '-');
        return strtolower($text);
    }

    protected function extractMeta(array $row, string $key, mixed $default = null): mixed
    {
        if (!isset($row['compact_meta'])) {
            return $default;
        }
        $meta = is_string($row['compact_meta'])
            ? json_decode($row['compact_meta'], true)
            : $row['compact_meta'];
        return $meta[$key] ?? $default;
    }
}
