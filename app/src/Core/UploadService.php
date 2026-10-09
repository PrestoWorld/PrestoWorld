<?php

declare(strict_types=1);

namespace PrestoWorld\Core;

/**
 * UploadService — wp_upload_dir (spec 10 §10.4 Group media).
 */
final class UploadService
{
    private function __construct()
    {
    }

    /**
     * @return array{path: string, url: string, subdir: string, basedir: string, baseurl: string, error: bool}
     */
    public static function dir(string $time = ''): array
    {
        $basedir = SiteInfo::uploadsDir() !== '' ? SiteInfo::uploadsDir() : Path::uploads();
        $baseurl = SiteInfo::uploadsUrl() !== '' ? SiteInfo::uploadsUrl() : Url::content('uploads');

        $subdir = '';
        if ($time !== '') {
            $ts = strtotime($time);
            if ($ts !== false) {
                $subdir = '/' . date('Y/m', $ts);
            }
        } else {
            $subdir = '/' . date('Y/m');
        }

        Filesystem::mkdirP($basedir . $subdir);

        return [
            'path' => $basedir . $subdir,
            'url' => $baseurl . $subdir,
            'subdir' => $subdir,
            'basedir' => $basedir,
            'baseurl' => $baseurl,
            'error' => false,
        ];
    }

    public static function checkFileType(string $file, array $mimes = []): bool
    {
        return in_array(strtolower(pathinfo($file, PATHINFO_EXTENSION)), array_keys($mimes === [] ? ['jpg' => true, 'png' => true, 'gif' => true, 'pdf' => true] : $mimes), true);
    }

    public static function checkFiletypeExt(string $file, array $mimes = []): bool
    {
        return self::checkFileType($file, $mimes);
    }

    public static function uniqueFilename(string $dir, string $name): string
    {
        $ext = pathinfo($name, PATHINFO_EXTENSION);
        $base = pathinfo($name, PATHINFO_FILENAME);
        $candidate = $dir . '/' . $name;
        $i = 1;

        while (file_exists($candidate)) {
            $candidate = $dir . '/' . $base . '-' . $i . ($ext !== '' ? '.' . $ext : '');
            $i++;
        }

        return basename($candidate);
    }

    /**
     * wp_upload_bits — ghi nội dung thô vào thư mục uploads (spec 10 §10.4.14).
     *
     * @param array<string, mixed>|null $time
     * @return array{file: string, url: string, type: string, error: false|string}
     */
    public static function bits(string $name, ?string $deprecated, string $bits, ?array $time = null): array
    {
        $dir = self::dir(is_string($time['time'] ?? null) ? (string) $time['time'] : '');
        $filename = self::uniqueFilename($dir['path'], $name);
        $path = $dir['path'] . '/' . $filename;

        $error = false;
        if (file_put_contents($path, $bits) === false) {
            $error = 'Could not write file ' . $path;
        }

        return [
            'file' => $path,
            'url' => $dir['url'] . '/' . $filename,
            'type' => self::mimeFor($filename),
            'error' => $error,
        ];
    }

    /**
     * wp_handle_upload — xử lý $_FILES entry (spec 10 §10.4.14).
     *
     * @param array<string, mixed> $file
     * @param array<string, mixed> $overrides
     * @param array<string, mixed>|null $time
     * @return array<string, mixed>
     */
    public static function handle(array $file, array $overrides = [], ?array $time = null): array
    {
        return self::storeFromFile($file, $time, 'upload');
    }

    /**
     * wp_handle_sideload — như handle nhưng cho file tải về (spec 10 §10.4.14).
     *
     * @param array<string, mixed> $file
     * @param array<string, mixed> $overrides
     * @param array<string, mixed>|null $time
     * @return array<string, mixed>
     */
    public static function sideload(array $file, array $overrides = [], ?array $time = null): array
    {
        return self::storeFromFile($file, $time, 'sideload');
    }

    /**
     * @param array<string, mixed> $file
     * @param array<string, mixed>|null $time
     * @return array<string, mixed>
     */
    private static function storeFromFile(array $file, ?array $time, string $context): array
    {
        $tmp = $file['tmp_name'] ?? '';
        $name = $file['name'] ?? '';
        if (!is_string($tmp) || !is_string($name) || $tmp === '') {
            return ['error' => 'Invalid ' . $context . ' payload.'];
        }

        $bits = file_get_contents($tmp);
        if ($bits === false) {
            return ['error' => 'Could not read ' . $context . ' source.'];
        }

        return self::bits($name, null, $bits, $time);
    }

    private static function mimeFor(string $file): string
    {
        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        $mimes = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'svg' => 'image/svg+xml',
            'pdf' => 'application/pdf',
            'txt' => 'text/plain',
            'csv' => 'text/csv',
            'json' => 'application/json',
            'zip' => 'application/zip',
        ];

        return $mimes[$ext] ?? 'application/octet-stream';
    }
}