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
}