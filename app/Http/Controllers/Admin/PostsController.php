<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use Cycle\Database\DatabaseInterface;
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
            ->where('post_status', '!=', 'auto-draft');

        $type = $_GET['type'] ?? null;
        if ($type !== null) {
            $query = $query->where('post_type', $type);
        } elseif ($this->isWordPress()) {
            $query = $query->where('post_type', 'post');
        }

        $rows = $query->orderBy('post_date', 'DESC')->fetchAll();

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
