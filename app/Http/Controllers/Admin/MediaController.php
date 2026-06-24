<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use Cycle\Database\DatabaseInterface;
use Witals\Framework\Http\Response;

class MediaController
{
    use AdminControllerHelperTrait;

    public function __construct(
        protected DatabaseInterface $db,
    ) {
        $this->initHelpers($db);
    }

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

        $cloudManager = $this->cloud();
        $cloudUrl = null;
        $source = 'presto';

        if ($cloudManager !== null) {
            try {
                $cloudUrl = $cloudManager->provider()->upload($relativePath, $destPath);
                $source = $cloudManager->driverName();
            } catch (\Throwable) {
                $cloudUrl = null;
            }
        }

        $localUrl = '/storage/uploads/' . $relativePath;
        $url = $cloudUrl ?? $this->resolveMediaUrl($localUrl);

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
            'alt' => '',
        ]);
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
            $fileSize = 0;
        } elseif ($subPath !== '' && file_exists($prestoPath)) {
            $actualPath = $prestoPath;
            $fileUrl = $this->resolveMediaUrl('/storage/uploads/' . $subPath);
            $source = 'presto';
            $fileSize = filesize($prestoPath);
        } elseif ($subPath !== '' && file_exists($wpPath)) {
            $actualPath = $wpPath;
            $fileUrl = $this->resolveMediaUrl('/wp-content/uploads/' . $subPath);
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
                $thumbnailUrl = $this->resolveMediaUrl('/storage/uploads/' . $thumbSubPath);
            } elseif (file_exists($wpThumb)) {
                $thumbnailUrl = $this->resolveMediaUrl('/wp-content/uploads/' . $thumbSubPath);
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
            'alt' => $row['post_excerpt'] ?? '',
        ];
    }

    protected function scanPrestoStorage(): array
    {
        $items = [];
        $storageDir = $this->getBasePath() . '/storage/uploads';

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

            $relativeUrl = '/storage/uploads/' . $relative;
            $items[] = [
                'id' => 0,
                'postId' => null,
                'title' => $file->getFilename(),
                'filename' => $file->getFilename(),
                'url' => $this->resolveMediaUrl($relativeUrl),
                'thumbnailUrl' => $this->resolveMediaUrl($relativeUrl),
                'date' => date('Y-m-d H:i:s', $file->getMTime()),
                'size' => $file->getSize(),
                'mimeType' => $mime,
                'dimensions' => $this->getImageDimensions($path),
                'source' => 'presto',
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
