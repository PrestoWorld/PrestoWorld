<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use Witals\Framework\Http\Response;
use Cycle\Database\DatabaseInterface;
use PrestoWorld\Contracts\Plugin\PluginStoreInterface;
use PrestoWorld\Theme\ThemeRepository;

class AdminApiController
{
    protected string $prefix;

    public function __construct(
        protected DatabaseInterface $db,
        protected PluginStoreInterface $plugins,
    ) {
        $this->prefix = getenv('PW_TABLE_PREFIX') ?: 'pw_';
    }

    // ── Posts ───────────────────────────────────────────────────

    public function posts(): Response
    {
        $rows = $this->db->select('*')
            ->from($this->prefix . 'posts')
            ->where('post_status', '!=', 'auto-draft')
            ->orderBy('post_date', 'DESC')
            ->fetchAll();

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

    // ── Plugins ─────────────────────────────────────────────────

    public function plugins(): Response
    {
        $installed = $this->plugins->getInstalledPlugins();

        $plugins = array_values(array_map(fn(array $row) => [
            'id' => $row['name'],
            'name' => $row['name'],
            'desc' => $row['metadata']['desc'] ?? $row['metadata']['description'] ?? '',
            'version' => $row['version'],
            'author' => $row['metadata']['author'] ?? 'Unknown',
            'active' => $row['enabled'],
            'updateAvailable' => $row['metadata']['update_available'] ?? false,
            'category' => $row['metadata']['category'] ?? 'Uncategorized',
        ], $installed));

        return Response::json($plugins);
    }

    // ── Themes ──────────────────────────────────────────────────

    public function themes(): Response
    {
        $themesDir = getenv('PW_CONTENT_DIR')
            ? getenv('PW_CONTENT_DIR') . '/themes'
            : null;

        $repo = new ThemeRepository($themesDir);
        $themes = $repo->getAll();

        return Response::json($themes);
    }

    public function activateTheme(\Witals\Framework\Http\Request $request): Response
    {
        $body = json_decode($request->body() ?? '{}', true);
        $theme = $body['theme'] ?? '';

        if ($theme === '') {
            return Response::json(['success' => false, 'error' => 'No theme specified'], 400);
        }

        $themesDir = getenv('PW_CONTENT_DIR')
            ? getenv('PW_CONTENT_DIR') . '/themes'
            : null;

        if ($themesDir === null || !is_dir($themesDir . '/' . $theme)) {
            return Response::json(['success' => false, 'error' => 'Theme not found'], 404);
        }

        putenv('PW_ACTIVE_THEME=' . $theme);
        $_ENV['PW_ACTIVE_THEME'] = $theme;

        $repo = new ThemeRepository($themesDir);
        $all = $repo->getAll();
        $name = $theme;
        foreach ($all as $t) {
            if ($t['directory'] === $theme) {
                $name = $t['name'];
                break;
            }
        }

        return Response::json([
            'success' => true,
            'theme' => $theme,
            'name' => $name,
        ]);
    }

    public function activateThemeFromForm(\Witals\Framework\Http\Request $request): Response
    {
        $theme = $request->post('theme', '');

        if ($theme === '') {
            return Response::redirect('/wp-admin/themes.php');
        }

        $themesDir = getenv('PW_CONTENT_DIR')
            ? getenv('PW_CONTENT_DIR') . '/themes'
            : null;

        if ($themesDir !== null && is_dir($themesDir . '/' . $theme)) {
            putenv('PW_ACTIVE_THEME=' . $theme);
            $_ENV['PW_ACTIVE_THEME'] = $theme;
        }

        return Response::redirect('/wp-admin/themes.php');
    }

    // ── Stats ───────────────────────────────────────────────────

    public function stats(): Response
    {
        try {
            $totalPosts = (int) ($this->db->select('COUNT(*) as count')
                ->from($this->prefix . 'posts')
                ->where('post_status', '!=', 'auto-draft')
                ->fetch()['count'] ?? 0);

            $publishedPosts = (int) ($this->db->select('COUNT(*) as count')
                ->from($this->prefix . 'posts')
                ->where('post_status', 'publish')
                ->fetch()['count'] ?? 0);

            $draftPosts = (int) ($this->db->select('COUNT(*) as count')
                ->from($this->prefix . 'posts')
                ->where('post_status', 'draft')
                ->fetch()['count'] ?? 0);
        } catch (\Throwable) {
            $totalPosts = $publishedPosts = $draftPosts = 0;
        }

        $installed = $this->plugins->getInstalledPlugins();
        $totalPlugins = count($installed);
        $activePlugins = count(array_filter($installed, fn($p) => $p['enabled']));

        return Response::json([
            'posts' => [
                'total' => $totalPosts,
                'published' => $publishedPosts,
                'draft' => $draftPosts,
            ],
            'plugins' => [
                'total' => $totalPlugins,
                'active' => $activePlugins,
                'inactive' => $totalPlugins - $activePlugins,
            ],
        ]);
    }

    // ── Activities ──────────────────────────────────────────────

    public function activities(): Response
    {
        try {
            $rows = $this->db->select('*')
                ->from($this->prefix . 'posts')
                ->where('post_status', '!=', 'auto-draft')
                ->orderBy('post_modified', 'DESC')
                ->limit(20)
                ->fetchAll();
        } catch (\Throwable) {
            $rows = [];
        }

        $activities = [];
        foreach ($rows as $row) {
            $title = $row['post_title'] ?? $row['title'] ?? 'Untitled';
            $status = $row['post_status'] ?? $row['status'] ?? '';
            $activities[] = [
                'id' => (int) ($row['ID'] ?? $row['id']),
                'text' => $status === 'publish'
                    ? "Published post: \"{$title}\""
                    : "Updated post: \"{$title}\"",
                'time' => $this->relativeTime($row['post_modified'] ?? $row['post_date'] ?? $row['updated_at'] ?? $row['created_at'] ?? ''),
                'type' => $status === 'publish' ? 'post' : 'update',
            ];
        }

        return Response::json($activities);
    }

    // ── Helpers ─────────────────────────────────────────────────

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

    protected function mapStatus(string $status): string
    {
        return match ($status) {
            'publish', 'published' => 'Published',
            'draft' => 'Draft',
            'scheduled' => 'Scheduled',
            default => 'Draft',
        };
    }

    protected function formatDate(mixed $date): string
    {
        if ($date instanceof \DateTimeInterface) {
            return $date->format('Y-m-d H:i');
        }
        if (is_string($date) && $date !== '') {
            return date('Y-m-d H:i', strtotime($date));
        }
        return date('Y-m-d H:i');
    }

    public function users(): Response
    {
        try {
            $rows = $this->db->select('*')
                ->from($this->prefix . 'users')
                ->orderBy('user_registered', 'DESC')
                ->fetchAll();
        } catch (\Throwable) {
            $rows = [];
        }

        $users = array_map(fn(array $row) => [
            'id' => (int) ($row['ID'] ?? $row['id']),
            'username' => $row['user_login'] ?? '',
            'name' => $row['display_name'] ?? $row['user_nicename'] ?? '',
            'email' => $row['user_email'] ?? '',
            'role' => $this->resolveUserRole((int) ($row['ID'] ?? $row['id'])),
            'registered' => $this->formatDate($row['user_registered'] ?? ''),
            'posts' => 0,
        ], $rows);

        return Response::json($users);
    }

    protected function resolveUserRole(int $userId): string
    {
        try {
            $meta = $this->db->select('meta_value')
                ->from($this->prefix . 'usermeta')
                ->where('user_id', $userId)
                ->where('meta_key', 'wp_capabilities')
                ->limit(1)
                ->fetch();
            if ($meta) {
                $caps = maybe_unserialize($meta['meta_value']);
                if (is_array($caps)) {
                    $roles = array_keys($caps);
                    return $roles[0] ?? 'subscriber';
                }
            }
        } catch (\Throwable) {
        }
        return 'subscriber';
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

    protected function relativeTime(mixed $datetime): string
    {
        $timestamp = $datetime instanceof \DateTimeInterface
            ? $datetime->getTimestamp()
            : (is_string($datetime) ? strtotime($datetime) : time());

        $diff = time() - $timestamp;

        return match (true) {
            $diff < 60 => 'Just now',
            $diff < 3600 => floor($diff / 60) . ' minutes ago',
            $diff < 86400 => floor($diff / 3600) . ' hours ago',
            $diff < 604800 => floor($diff / 86400) . ' days ago',
            default => date('M j', $timestamp),
        };
    }
}
