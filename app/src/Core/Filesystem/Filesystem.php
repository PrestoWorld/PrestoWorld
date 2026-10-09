<?php

declare(strict_types=1);

namespace PrestoWorld\Core\Filesystem;

use PrestoWorld\Core\Filesystem as FilesystemService;

/**
 * Filesystem — replaces WP_Filesystem_Base.
 */
class Filesystem
{
    public string $method = 'direct';
    public string $error = '';

    /**
     * @var array<string, mixed>
     */
    protected array $args = [];

    /**
     * @param array<string, mixed> $args
     */
    public function __construct(array $args = [])
    {
        $this->args = $args;
    }

    public function get_contents(string $file): string|false
    {
        if (!is_file($file)) {
            return false;
        }

        return @file_get_contents($file);
    }

    public function put_contents(string $file, string $contents, int $mode = 0, bool $wp_filesystem_override = false): bool
    {
        $dir = dirname($file);
        if (!is_dir($dir)) {
            FilesystemService::mkdirP($dir);
        }

        return @file_put_contents($file, $contents) !== false;
    }

    public function exists(string $file): bool
    {
        return file_exists($file);
    }

    public function is_file(string $file): bool
    {
        return is_file($file);
    }

    public function is_dir(string $file): bool
    {
        return is_dir($file);
    }

    public function is_writable(string $file): bool
    {
        return FilesystemService::isWritable($file);
    }

    public function is_readable(string $file): bool
    {
        return is_readable($file);
    }

    public function delete(string $file, bool $recursive = false, string $type = ''): bool
    {
        if (is_dir($file)) {
            return $recursive ? FilesystemService::deleteDir($file) : @rmdir($file);
        }

        return @unlink($file);
    }

    public function mkdir(string $path, int $chmod = 0755, bool $recursive = false): bool
    {
        return $recursive ? FilesystemService::mkdirP($path, $chmod) : @mkdir($path, $chmod);
    }

    public function rmdir(string $path, bool $recursive = false): bool
    {
        return $this->delete($path, $recursive);
    }

    public function copy(string $source, string $destination, bool $overwrite = false): bool
    {
        if (!$overwrite && file_exists($destination)) {
            return false;
        }

        if (is_dir($source)) {
            return FilesystemService::copyDir($source, $destination);
        }

        return @copy($source, $destination);
    }

    public function move(string $source, string $destination, bool $overwrite = false): bool
    {
        if (!$overwrite && file_exists($destination)) {
            return false;
        }

        if (is_dir($source)) {
            return FilesystemService::moveDir($source, $destination);
        }

        return @rename($source, $destination);
    }

    public function chmod(string $file, int $mode = 0, bool $recursive = false): bool
    {
        if ($mode === 0) {
            $mode = is_dir($file) ? 0755 : 0644;
        }

        return @chmod($file, $mode);
    }

    public function size(string $file): int|false
    {
        $size = @filesize($file);

        return $size === false ? false : $size;
    }

    /**
     * @return array<int, string>|false
     */
    public function dirlist(string $path, bool $include_hidden = false, bool $recursive = false): array|false
    {
        if (!is_dir($path)) {
            return false;
        }

        $entries = scandir($path);
        if ($entries === false) {
            return false;
        }

        $result = [];
        foreach ($entries as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            if (!$include_hidden && str_starts_with($entry, '.')) {
                continue;
            }

            $full = rtrim($path, '/\\') . '/' . $entry;
            $result[] = $full;

            if ($recursive && is_dir($full)) {
                $sub = $this->dirlist($full, $include_hidden, true);
                if (is_array($sub)) {
                    foreach ($sub as $item) {
                        $result[] = $item;
                    }
                }
            }
        }

        return $result;
    }

    public function getchmod(string $file): string|false
    {
        $perms = @fileperms($file);
        if ($perms === false) {
            return false;
        }

        return substr(sprintf('%o', $perms), -4);
    }

    public function find_base_dir(string $base, string $folder = ''): string
    {
        return rtrim($base, '/\\') . ($folder !== '' ? '/' . trim($folder, '/\\') : '');
    }

    public function abspath(): string
    {
        return $this->constantPath('ABSPATH', getcwd() ?: '');
    }

    public function wp_content_dir(): string
    {
        return $this->constantPath('WP_CONTENT_DIR', $this->abspath() . '/wp-content');
    }

    public function wp_plugins_dir(): string
    {
        return $this->constantPath('WP_PLUGIN_DIR', $this->wp_content_dir() . '/plugins');
    }

    public function wp_themes_dir(): string
    {
        return $this->constantPath('WP_THEME_DIR', $this->wp_content_dir() . '/themes');
    }

    private function constantPath(string $name, string $default): string
    {
        if (defined($name)) {
            $value = constant($name);
            if (is_string($value) && $value !== '') {
                return $value;
            }
        }

        return $default;
    }
}
