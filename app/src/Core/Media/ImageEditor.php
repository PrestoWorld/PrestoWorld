<?php

declare(strict_types=1);

namespace PrestoWorld\Core\Media;

use PrestoWorld\Core\Error\PrestoError;

/**
 * ImageEditor — replaces WP_Image_Editor.
 *
 * @phpstan-consistent-constructor
 */
class ImageEditor
{
    /** @var array<string, string> */
    private const MIME_EXTENSIONS = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'image/webp' => 'webp',
        'image/avif' => 'avif',
        'image/bmp' => 'bmp',
        'image/tiff' => 'tiff',
    ];

    public ?string $file = null;

    public string $mime_type = '';

    public ?string $error = null;

    protected int $width = 0;

    protected int $height = 0;

    protected int $quality = 82;

    protected bool $loaded = false;

    /**
     * @param array<string, mixed> $args
     */
    public function __construct(array $args = [])
    {
        $file = $args['file'] ?? null;
        if (is_string($file)) {
            $this->file = $file;
        }

        $mime = $args['mime_type'] ?? null;
        if (is_string($mime)) {
            $this->mime_type = $mime;
        }
    }

    /**
     * @param array<string, mixed> $args
     */
    public static function get_instance(string $file, array $args = []): self|PrestoError
    {
        if ($file === '') {
            return new PrestoError('image_no_file', 'No image file was provided.');
        }

        $args['file'] = $file;

        return new static($args);
    }

    /**
     * @param array<string, mixed> $args
     */
    public static function test(array $args = []): bool
    {
        return true;
    }

    public static function supports_mime_type(string $mime_type): bool
    {
        return isset(self::MIME_EXTENSIONS[$mime_type]);
    }

    public function load(): bool
    {
        if ($this->file === null) {
            $this->error = 'The image file is not set.';

            return false;
        }

        $this->loaded = true;

        return true;
    }

    /**
     * @param array<string, mixed>|string|null $mime_type
     * @return array<string, mixed>|PrestoError
     */
    public function save(string $filename = '', string|array|null $mime_type = null): array|PrestoError
    {
        if (!$this->loaded && !$this->load()) {
            return $this->fail('image_load_failed', 'The image could not be loaded.');
        }

        if ($filename !== '') {
            $this->file = $filename;
        }

        if (is_string($mime_type)) {
            $this->mime_type = $mime_type;
        } elseif (is_array($mime_type) && is_string($mime_type['mime_type'] ?? null)) {
            $this->mime_type = $mime_type['mime_type'];
        }

        $size = $this->get_size();
        if ($size === false) {
            $size = ['width' => $this->width, 'height' => $this->height];
        }

        return [
            'file' => $this->file ?? '',
            'width' => $size['width'],
            'height' => $size['height'],
            'mime-type' => $this->mime_type,
        ];
    }

    public function resize(int $max_w, int $max_h = 0, bool $crop = false): bool|PrestoError
    {
        $size = $this->get_size();
        if ($size === false) {
            return $this->fail('image_load_failed', 'The image could not be loaded.');
        }

        $dimensions = $this->resize_dimensions($size['width'], $size['height'], $max_w, $max_h, $crop);
        if ($dimensions === false) {
            return $this->fail('image_resize_failed', 'The image could not be resized.');
        }

        $this->width = $dimensions[0];
        $this->height = $dimensions[1];

        return true;
    }

    public function crop(
        int $src_x = 0,
        int $src_y = 0,
        int $src_w = 0,
        int $src_h = 0,
        int $dst_w = 0,
        int $dst_h = 0,
        bool $src_abs = false,
        bool $dst_abs = true
    ): bool|PrestoError {
        $src_w = $src_w > 0 ? $src_w : $this->width;
        $src_h = $src_h > 0 ? $src_h : $this->height;

        $this->width = $dst_w > 0 ? $dst_w : $src_w;
        $this->height = $dst_h > 0 ? $dst_h : $src_h;

        return true;
    }

    public function rotate(float $angle): bool|PrestoError
    {
        return true;
    }

    public function flip(bool $horz, bool $vert = false): bool|PrestoError
    {
        return true;
    }

    /**
     * @return array{width: int, height: int}|false
     */
    public function get_size(): array|false
    {
        if ($this->file !== null && is_file($this->file)) {
            $size = @getimagesize($this->file);
            if (is_array($size)) {
                $this->width = $size[0];
                $this->height = $size[1];
            }
        }

        if ($this->width <= 0 || $this->height <= 0) {
            return false;
        }

        return ['width' => $this->width, 'height' => $this->height];
    }

    /**
     * @param array<string, array<string, mixed>> $sizes
     * @return array<string, array<string, mixed>>
     */
    public function multi_resize(array $sizes): array
    {
        $resized = [];

        foreach ($sizes as $name => $data) {
            $width = is_numeric($data['width'] ?? null) ? (int) $data['width'] : 0;
            $height = is_numeric($data['height'] ?? null) ? (int) $data['height'] : 0;
            $crop = (bool) ($data['crop'] ?? false);

            $dimensions = $this->resize_dimensions($this->width, $this->height, $width, $height, $crop);
            if ($dimensions === false) {
                continue;
            }

            $resized[$name] = [
                'file' => $this->generate_filename('-' . $name),
                'width' => $dimensions[0],
                'height' => $dimensions[1],
                'mime-type' => $this->mime_type,
            ];
        }

        return $resized;
    }

    /**
     * @param array<string, mixed>|string|null $mime_type
     */
    public function stream(string|array|null $mime_type = null): bool
    {
        return true;
    }

    public function generate_filename(string $suffix = '', ?string $dest_path = null): string
    {
        $file = $this->file ?? '';
        $name = $file !== '' ? basename($file) : 'image';
        $extension = pathinfo($name, PATHINFO_EXTENSION);
        $base = $extension !== '' ? substr($name, 0, -strlen($extension) - 1) : $name;
        $filename = $base . $suffix . ($extension !== '' ? '.' . $extension : '');

        $directory = $dest_path ?? ($file !== '' ? dirname($file) : '');
        if ($directory === '' || $directory === '.') {
            return $filename;
        }

        return rtrim($directory, '/') . '/' . $filename;
    }

    public function get_suffix(): string
    {
        return self::MIME_EXTENSIONS[$this->mime_type] ?? '';
    }

    public function set_quality(int $quality): bool
    {
        if ($quality < 0) {
            return false;
        }

        $this->quality = $quality;

        return true;
    }

    public function get_quality(): int
    {
        return $this->quality;
    }

    /**
     * @return array{0: int, 1: int, 2: int, 3: int}|false
     */
    public function resize_dimensions(int $orig_w, int $orig_h, int $dest_w, int $dest_h, bool $crop = false): array|false
    {
        if ($orig_w <= 0 || $orig_h <= 0) {
            return false;
        }

        $orig_w = max(1, $orig_w);
        $orig_h = max(1, $orig_h);

        if ($dest_w <= 0 && $dest_h <= 0) {
            $dest_w = $orig_w;
            $dest_h = $orig_h;
        }

        $ratio_orig = $orig_w / $orig_h;

        if ($dest_w <= 0) {
            $dest_w = max(1, (int) round($dest_h * $ratio_orig));
        }

        if ($dest_h <= 0) {
            $dest_h = max(1, (int) round($dest_w / $ratio_orig));
        }

        if ($crop) {
            $ratio_dest = $dest_w / $dest_h;

            if ($ratio_dest < $ratio_orig) {
                $dst_w = max(1, (int) round($dest_h * $ratio_orig));
                $dst_h = $dest_h;
                $src_x = (int) round(($dst_w - $dest_w) / 2);
                $src_y = 0;
            } else {
                $dst_w = $dest_w;
                $dst_h = max(1, (int) round($dest_w / $ratio_orig));
                $src_x = 0;
                $src_y = (int) round(($dst_h - $dest_h) / 2);
            }

            return [$dst_w, $dst_h, $src_x, $src_y];
        }

        if ($dest_w >= $orig_w && $dest_h >= $orig_h) {
            return [$orig_w, $orig_h, 0, 0];
        }

        $ratio = min($dest_w / $orig_w, $dest_h / $orig_h);
        $dst_w = max(1, (int) round($orig_w * $ratio));
        $dst_h = max(1, (int) round($orig_h * $ratio));

        return [$dst_w, $dst_h, 0, 0];
    }

    private function fail(string $code, string $message): PrestoError
    {
        $error = new PrestoError($code, $message);

        $this->error = $error->get_error_message();

        return $error;
    }
}
