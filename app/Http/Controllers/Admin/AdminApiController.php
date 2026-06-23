<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use Witals\Framework\Http\Response;
use Cycle\Database\DatabaseInterface;
use PrestoWorld\Contracts\Plugin\PluginStoreInterface;
use PrestoWorld\Theme\ThemeRepository;
use App\Storage\CloudStorageManager;

class AdminApiController
{
    protected string $prefix;
    protected bool $wordPressMode;
    protected ?CloudStorageManager $cloud = null;

    public function __construct(
        protected DatabaseInterface $db,
        protected PluginStoreInterface $plugins,
    ) {
        $this->prefix = getenv('PW_TABLE_PREFIX') ?: 'pw_';
        $this->wordPressMode = getenv('PW_CONTENT_DIR') !== false && $this->prefix === 'wp_';
    }

    protected function isWordPress(): bool
    {
        return $this->wordPressMode;
    }

    protected function cloud(): ?CloudStorageManager
    {
        if ($this->cloud === null) {
            $mgr = new CloudStorageManager();
            if ($mgr->isEnabled()) {
                $this->cloud = $mgr;
            }
        }
        return $this->cloud;
    }

    // ── Posts ───────────────────────────────────────────────────

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
            $baseQuery = $this->db->select('COUNT(*) as count')
                ->from($this->prefix . 'posts')
                ->where('post_status', '!=', 'auto-draft');

            $pubQuery = $this->db->select('COUNT(*) as count')
                ->from($this->prefix . 'posts')
                ->where('post_status', 'publish');

            $draftQuery = $this->db->select('COUNT(*) as count')
                ->from($this->prefix . 'posts')
                ->where('post_status', 'draft');

            if ($this->isWordPress()) {
                $baseQuery = $baseQuery->where('post_type', 'post');
                $pubQuery = $pubQuery->where('post_type', 'post');
                $draftQuery = $draftQuery->where('post_type', 'post');
            }

            $totalPosts = (int) ($baseQuery->fetch()['count'] ?? 0);
            $publishedPosts = (int) ($pubQuery->fetch()['count'] ?? 0);
            $draftPosts = (int) ($draftQuery->fetch()['count'] ?? 0);
        } catch (\Throwable) {
            $totalPosts = $publishedPosts = $draftPosts = 0;
        }

        // Post type breakdown (WordPress mode only)
        $byPostType = [];
        if ($this->isWordPress()) {
            try {
                $rows = $this->db->select(['post_type', 'COUNT(*) as count'])
                    ->from($this->prefix . 'posts')
                    ->where('post_status', '!=', 'auto-draft')
                    ->groupBy('post_type')
                    ->fetchAll();

                $labels = [
                    'post' => 'Posts',
                    'page' => 'Pages',
                    'attachment' => 'Media',
                    'revision' => 'Revisions',
                    'nav_menu_item' => 'Menu Items',
                    'customize_changeset' => 'Changesets',
                ];

                foreach ($rows as $row) {
                    $type = $row['post_type'] ?? 'unknown';
                    $byPostType[] = [
                        'type' => $type,
                        'count' => (int) ($row['count'] ?? 0),
                        'label' => $labels[$type] ?? ucfirst($type),
                    ];
                }
            } catch (\Throwable) {
                $byPostType = [];
            }
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
            'byPostType' => $byPostType,
        ]);
    }

    // ── Activities ──────────────────────────────────────────────

    public function activities(): Response
    {
        try {
            $query = $this->db->select('*')
                ->from($this->prefix . 'posts')
                ->where('post_status', '!=', 'auto-draft');

            if ($this->isWordPress()) {
                $query = $query->where('post_type', 'post');
            }

            $rows = $query->orderBy('post_modified', 'DESC')
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
            $diff < 604800 => floor($diff / 8640) . ' days ago',
            default => date('M j', $timestamp),
        };
    }

    // ── Media Library ────────────────────────────────────────────

    public function media(): Response
    {
        $items = [];

        if ($this->isWordPress()) {
            try {
                $rows = $this->db->select('*')
                    ->from($this->prefix . 'posts')
                    ->where('post_type', 'attachment')
                    ->where('post_status', '!=', 'auto-draft')
                    ->orderBy('post_date', 'DESC')
                    ->fetchAll();

                foreach ($rows as $row) {
                    $id = (int) ($row['ID'] ?? $row['id']);
                    $item = $this->buildWpMediaItem($row, $id);
                    if ($item !== null) {
                        $items[] = $item;
                    }
                }
            } catch (\Throwable) {
            }
        }

        foreach ($this->scanPrestoStorage() as $pf) {
            $dup = false;
            foreach ($items as $existing) {
                if ($existing['filename'] === $pf['filename'] && abs($existing['size'] - $pf['size']) < 1) {
                    $dup = true;
                    break;
                }
            }
            if (!$dup) {
                $items[] = $pf;
            }
        }

        usort($items, fn(array $a, array $b): int => strcmp($b['date'] ?? '', $a['date'] ?? ''));

        return Response::json($items);
    }

    public function offloadMedia(int $id): Response
    {
        if (!$this->isWordPress()) {
            return Response::json(['error' => 'Offload requires WordPress mode'], 400);
        }

        try {
            $row = $this->db->select('*')
                ->from($this->prefix . 'posts')
                ->where('ID', $id)
                ->where('post_type', 'attachment')
                ->limit(1)
                ->fetch();
        } catch (\Throwable) {
            $row = null;
        }

        if (!$row) {
            return Response::json(['error' => 'Attachment not found'], 404);
        }

        $result = $this->copyToPrestoStorage((int) $row['ID']);

        // Also upload to cloud storage
        $cloudManager = $this->cloud();
        if ($result !== null && $cloudManager !== null) {
            $cloudResult = [];
            foreach ($result as $file) {
                $localPath = $this->getBasePath() . '/storage/uploads/' . $file['file'];
                if (file_exists($localPath)) {
                    try {
                        $url = $cloudManager->provider()->upload($file['file'], $localPath);
                        $cloudResult[] = ['file' => $file['file'], 'url' => $url];
                    } catch (\Throwable) {
                        $cloudResult[] = ['file' => $file['file'], 'error' => 'cloud_upload_failed'];
                    }
                }
            }
            return Response::json(['success' => true, 'files' => $result, 'cloud' => $cloudResult]);
        }

        if ($result === null) {
            return Response::json(['error' => 'Source file not found'], 404);
        }

        return Response::json(['success' => true, 'files' => $result]);
    }

    public function uploadMedia(): Response
    {
        $file = $_FILES['file'] ?? null;
        if ($file === null || $file['error'] !== UPLOAD_ERR_OK) {
            return Response::json(['error' => 'No file uploaded or upload error'], 400);
        }

        $now = new \DateTimeImmutable();
        $year = $now->format('Y');
        $month = $now->format('m');
        $subDir = "{$year}/{$month}";
        $storageDir = $this->getBasePath() . '/storage/uploads/' . $subDir;

        if (!is_dir($storageDir)) {
            mkdir($storageDir, 0755, true);
        }

        $origName = basename($file['name']);
        $destPath = $storageDir . '/' . $origName;
        $counter = 1;
        while (file_exists($destPath)) {
            $info = pathinfo($origName);
            $destPath = $storageDir . '/' . $info['filename'] . '-' . $counter . '.' . ($info['extension'] ?? '');
            $counter++;
        }

        if (!move_uploaded_file($file['tmp_name'], $destPath)) {
            return Response::json(['error' => 'Failed to save file'], 500);
        }

        $filename = basename($destPath);
        $relativePath = $subDir . '/' . $filename;
        $mime = mime_content_type($destPath) ?: 'application/octet-stream';
        $size = filesize($destPath);

        // Upload to cloud storage first (cloud-first strategy)
        $cloudManager = $this->cloud();
        $cloudUrl = null;
        $source = 'presto';
        $offloaded = true;

        if ($cloudManager !== null) {
            try {
                $cloudUrl = $cloudManager->provider()->upload($relativePath, $destPath);
                $source = $cloudManager->driverName();
            } catch (\Throwable $e) {
                // Cloud upload failed, fall back to local
                $cloudUrl = null;
            }
        }

        $url = $cloudUrl ?? ('/storage/uploads/' . $relativePath);

        if ($this->isWordPress()) {
            try {
                $postId = $this->createAttachmentPost($filename, $relativePath, $mime);
            } catch (\Throwable) {
                $postId = null;
            }
        } else {
            $postId = null;
        }

        return Response::json([
            'id' => $postId ?? 0,
            'postId' => $postId,
            'title' => pathinfo($filename, PATHINFO_FILENAME),
            'filename' => $filename,
            'url' => $url,
            'thumbnailUrl' => $url,
            'date' => $now->format('Y-m-d H:i:s'),
            'size' => $size,
            'mimeType' => $mime,
            'dimensions' => $this->getImageDimensions($destPath),
            'source' => $source,
            'offloaded' => $offloaded,
            'alt' => '',
        ]);
    }

    // ── Media Helpers ────────────────────────────────────────────

    protected function buildWpMediaItem(array $row, int $id): ?array
    {
        $attachedFile = null;
        $wpMeta = null;

        try {
            $meta = $this->db->select('meta_value')
                ->from($this->prefix . 'postmeta')
                ->where('post_id', $id)
                ->where('meta_key', '_wp_attached_file')
                ->limit(1)
                ->fetch();
            $attachedFile = $meta['meta_value'] ?? null;

            $meta2 = $this->db->select('meta_value')
                ->from($this->prefix . 'postmeta')
                ->where('post_id', $id)
                ->where('meta_key', '_wp_attachment_metadata')
                ->limit(1)
                ->fetch();
            $wpMeta = $meta2 ? (is_string($meta2['meta_value']) ? @unserialize($meta2['meta_value']) : null) : null;
        } catch (\Throwable) {
        }

        $subPath = $attachedFile ?? '';
        if ($subPath === '' && ($row['post_mime_type'] ?? '') === '') {
            return null;
        }

        $basePath = $this->getBasePath();
        $prestoPath = $basePath . '/storage/uploads/' . $subPath;
        $contentDir = getenv('PW_CONTENT_DIR') ?: $basePath . '/public/wp-content';
        $wpPath = $contentDir . '/uploads/' . $subPath;

        $actualPath = null;
        $fileUrl = '';
        $source = 'wordpress';
        $fileSize = 0;

        // Check cloud storage first (cloud-first strategy)
        $cloudManager = $this->cloud();
        $cloudUrl = null;
        if ($subPath !== '' && $cloudManager !== null) {
            try {
                if ($cloudManager->provider()->exists($subPath)) {
                    $cloudUrl = $cloudManager->provider()->url($subPath);
                }
            } catch (\Throwable) {
            }
        }

        if ($cloudUrl !== null) {
            $fileUrl = $cloudUrl;
            $source = $cloudManager->driverName();
            $fileSize = 0; // cloud files don't have local size
        } elseif ($subPath !== '' && file_exists($prestoPath)) {
            $actualPath = $prestoPath;
            $fileUrl = '/storage/uploads/' . $subPath;
            $source = 'presto';
            $fileSize = filesize($prestoPath);
        } elseif ($subPath !== '' && file_exists($wpPath)) {
            $actualPath = $wpPath;
            $fileUrl = '/wp-content/uploads/' . $subPath;
            $fileSize = filesize($wpPath);
        }

        $filename = basename($subPath !== '' ? $subPath : "attachment-{$id}");
        $mime = $actualPath ? (mime_content_type($actualPath) ?: ($row['post_mime_type'] ?? '')) : ($row['post_mime_type'] ?? '');
        $dimensions = null;
        $thumbnailUrl = '';

        if ($wpMeta && isset($wpMeta['width'], $wpMeta['height'])) {
            $dimensions = ['width' => (int) $wpMeta['width'], 'height' => (int) $wpMeta['height']];
        }

        if ($wpMeta && isset($wpMeta['sizes']['thumbnail']['file'])) {
            $thumbFile = $wpMeta['sizes']['thumbnail']['file'];
            $thumbDir = dirname($subPath);
            $thumbSubPath = $thumbDir !== '.' ? $thumbDir . '/' . $thumbFile : $thumbFile;

            $prestoThumb = $basePath . '/storage/uploads/' . $thumbSubPath;
            $wpThumb = $contentDir . '/uploads/' . $thumbSubPath;

            if (file_exists($prestoThumb)) {
                $thumbnailUrl = '/storage/uploads/' . $thumbSubPath;
            } elseif (file_exists($wpThumb)) {
                $thumbnailUrl = '/wp-content/uploads/' . $thumbSubPath;
            }
        }

        if ($thumbnailUrl === '' && $fileUrl !== '') {
            $thumbnailUrl = $fileUrl;
        }

        return [
            'id' => $id,
            'postId' => $id,
            'title' => $row['post_title'] !== '' ? $row['post_title'] : pathinfo($filename, PATHINFO_FILENAME),
            'filename' => $filename,
            'url' => $fileUrl,
            'thumbnailUrl' => $thumbnailUrl,
            'date' => $this->formatDate($row['post_date'] ?? ''),
            'size' => $fileSize,
            'mimeType' => $mime,
            'dimensions' => $dimensions,
            'source' => $source,
            'offloaded' => $cloudUrl !== null || file_exists($prestoPath),
            'alt' => $row['post_excerpt'] ?? '',
        ];
    }

    protected function scanPrestoStorage(): array
    {
        $items = [];
        $storageDir = $this->getBasePath() . '/storage/uploads';

        // List from cloud storage first (cloud-first)
        $cloudManager = $this->cloud();
        if ($cloudManager !== null) {
            try {
                $cloudFiles = $cloudManager->provider()->list();
                foreach ($cloudFiles as $cf) {
                    $mime = $this->guessMime($cf['path']);
                    $dimensions = null;
                    $localPath = $storageDir . '/' . $cf['path'];
                    if (file_exists($localPath)) {
                        $dimensions = $this->getImageDimensions($localPath);
                    }
                    $items[] = [
                        'id' => 0,
                        'postId' => null,
                        'title' => basename($cf['path']),
                        'filename' => basename($cf['path']),
                        'url' => $cloudManager->provider()->url($cf['path']),
                        'thumbnailUrl' => $cloudManager->provider()->url($cf['path']),
                        'date' => $cf['mtime'] > 0 ? date('Y-m-d H:i:s', $cf['mtime']) : date('Y-m-d H:i:s'),
                        'size' => $cf['size'],
                        'mimeType' => $mime,
                        'dimensions' => $dimensions,
                        'source' => $cloudManager->driverName(),
                        'offloaded' => true,
                        'alt' => '',
                    ];
                }
            } catch (\Throwable) {
            }
        }

        if (!is_dir($storageDir)) {
            return $items;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($storageDir, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->isDir() || $file->getFilename()[0] === '.') {
                continue;
            }

            $path = $file->getPathname();
            $relative = str_replace($storageDir . '/', '', $path);

            // Skip if already included from cloud listing
            $dup = false;
            foreach ($items as $existing) {
                if ($existing['filename'] === $file->getFilename()) {
                    $dup = true;
                    break;
                }
            }
            if ($dup) {
                continue;
            }

            $mime = mime_content_type($path) ?: 'application/octet-stream';

            $items[] = [
                'id' => 0,
                'postId' => null,
                'title' => $file->getFilename(),
                'filename' => $file->getFilename(),
                'url' => '/storage/uploads/' . $relative,
                'thumbnailUrl' => '/storage/uploads/' . $relative,
                'date' => date('Y-m-d H:i:s', $file->getMTime()),
                'size' => $file->getSize(),
                'mimeType' => $mime,
                'dimensions' => $this->getImageDimensions($path),
                'source' => 'presto',
                'offloaded' => true,
                'alt' => '',
            ];
        }

        return $items;
    }

    protected function guessMime(string $path): string
    {
        static $mimes = [
            'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png',
            'gif' => 'image/gif', 'webp' => 'image/webp', 'svg' => 'image/svg+xml',
            'mp4' => 'video/mp4', 'webm' => 'video/webm', 'mov' => 'video/quicktime',
            'mp3' => 'audio/mpeg', 'wav' => 'audio/wav', 'ogg' => 'audio/ogg',
            'pdf' => 'application/pdf', 'zip' => 'application/zip',
            'doc' => 'application/msword', 'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ];
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        return $mimes[$ext] ?? 'application/octet-stream';
    }

    protected function createAttachmentPost(string $filename, string $relativePath, string $mime): int
    {
        $now = date('Y-m-d H:i:s');
        $title = pathinfo($filename, PATHINFO_FILENAME);

        $data = [
            'post_author' => 1,
            'post_date' => $now,
            'post_date_gmt' => gmdate('Y-m-d H:i:s'),
            'post_title' => $title,
            'post_status' => 'inherit',
            'comment_status' => 'closed',
            'ping_status' => 'closed',
            'post_name' => strtolower(str_replace([' ', '_'], '-', $title)),
            'post_modified' => $now,
            'post_modified_gmt' => gmdate('Y-m-d H:i:s'),
            'post_parent' => 0,
            'menu_order' => 0,
            'post_type' => 'attachment',
            'post_mime_type' => $mime,
        ];

        $keys = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        $this->db->execute(
            "INSERT INTO {$this->prefix}posts ({$keys}) VALUES ({$placeholders})",
            array_values($data)
        );

        $postId = (int) $this->db->getDriver()->getLastInsertID();

        // Store _wp_attached_file meta
        $this->db->execute(
            "INSERT INTO {$this->prefix}postmeta (post_id, meta_key, meta_value) VALUES (?, '_wp_attached_file', ?)",
            [$postId, $relativePath]
        );

        return $postId;
    }

    protected function copyToPrestoStorage(int $postId): ?array
    {
        $meta = $this->db->select('meta_value')
            ->from($this->prefix . 'postmeta')
            ->where('post_id', $postId)
            ->where('meta_key', '_wp_attached_file')
            ->limit(1)
            ->fetch();

        $subPath = $meta['meta_value'] ?? '';
        if ($subPath === '') {
            return null;
        }

        $basePath = $this->getBasePath();
        $contentDir = getenv('PW_CONTENT_DIR') ?: $basePath . '/public/wp-content';
        $wpFile = $contentDir . '/uploads/' . $subPath;

        if (!file_exists($wpFile)) {
            return null;
        }

        $prestoFile = $basePath . '/storage/uploads/' . $subPath;
        $copied = [$this->doCopyFile($wpFile, $prestoFile)];

        // Also copy thumbnail sizes
        $meta2 = $this->db->select('meta_value')
            ->from($this->prefix . 'postmeta')
            ->where('post_id', $postId)
            ->where('meta_key', '_wp_attachment_metadata')
            ->limit(1)
            ->fetch();

        if ($meta2) {
            $wpMeta = is_string($meta2['meta_value']) ? @unserialize($meta2['meta_value']) : null;
            if ($wpMeta && isset($wpMeta['sizes'])) {
                $dir = dirname($subPath);
                foreach ($wpMeta['sizes'] as $sizeName => $sizeData) {
                    $thumbFile = $sizeData['file'] ?? '';
                    if ($thumbFile === '') continue;
                    $thumbSubPath = ($dir !== '.' ? $dir . '/' : '') . $thumbFile;
                    $wpThumb = $contentDir . '/uploads/' . $thumbSubPath;
                    $prestoThumb = $basePath . '/storage/uploads/' . $thumbSubPath;
                    if (file_exists($wpThumb)) {
                        $copied[] = $this->doCopyFile($wpThumb, $prestoThumb);
                    }
                }
            }
        }

        return $copied;
    }

    protected function doCopyFile(string $from, string $to): array
    {
        $toDir = dirname($to);
        if (!is_dir($toDir)) {
            mkdir($toDir, 0755, true);
        }
        copy($from, $to);
        $relative = str_replace($this->getBasePath() . '/storage/uploads/', '', $to);
        return [
            'file' => $relative,
            'size' => filesize($to),
        ];
    }

    protected function getBasePath(): string
    {
        static $base = null;
        if ($base === null) {
            $base = defined('PW_BASE_PATH') ? PW_BASE_PATH : getcwd();
        }
        return $base;
    }

    protected function getImageDimensions(string $path): ?array
    {
        if (!file_exists($path)) {
            return null;
        }
        $info = @getimagesize($path);
        if ($info === false) {
            return null;
        }
        return ['width' => $info[0], 'height' => $info[1]];
    }
}
}
