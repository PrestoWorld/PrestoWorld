<?php

declare(strict_types=1);

namespace PrestoWorld\Core;

use PrestoWorld\Core\Error\PrestoError;
use PrestoWorld\Core\User\UserEntity;

/**
 * MediaService — get_avatar + attachment APIs (spec 10 §10.4.7 & §10.4.15).
 *
 * Attachment metadata lưu trên post meta (PostEntity dynamic meta) theo key WP:
 * `_wp_attached_file`, `_wp_attachment_metadata`, `_wp_attachment_image_alt`.
 */
final class MediaService
{
    private function __construct()
    {
    }

    public static function avatarUrl(int|string $idOrEmail, int $size = 96, string $default = '', string $rating = ''): string|false
    {
        $email = self::resolveEmail($idOrEmail);
        if ($email === '') {
            return false;
        }

        $hash = md5(strtolower(trim($email)));
        $query = http_build_query(array_filter([
            's' => $size,
            'd' => $default !== '' ? $default : null,
            'r' => $rating !== '' ? $rating : null,
        ]));

        return 'https://secure.gravatar.com/avatar/' . $hash . ($query !== '' ? '?' . $query : '');
    }

    /**
     * @param array<string, mixed> $args
     */
    public static function avatar(int|string $idOrEmail, int $size = 96, string $default = '', string $alt = '', array $args = []): string
    {
        $url = self::avatarUrl($idOrEmail, $size, $default, self::argString($args, 'rating'));
        if ($url === false) {
            return '';
        }

        $class = trim('avatar avatar-' . $size . ' photo ' . self::argString($args, 'class'));

        return sprintf(
            '<img alt="%s" src="%s" class="%s" height="%d" width="%d" />',
            htmlspecialchars($alt !== '' ? $alt : self::resolveEmail($idOrEmail), ENT_QUOTES),
            htmlspecialchars($url, ENT_QUOTES),
            htmlspecialchars($class, ENT_QUOTES),
            $size,
            $size,
        );
    }

    public static function file(int $postId): string|false
    {
        $file = MetaRepository::get('post', $postId, '_wp_attached_file', true);

        return is_string($file) && $file !== '' ? $file : false;
    }

    public static function updateFile(int $postId, string $file): void
    {
        MetaRepository::update('post', $postId, '_wp_attached_file', $file);
    }

    public static function url(int $postId): string|false
    {
        $file = self::file($postId);
        if ($file === false) {
            return false;
        }

        $dir = UploadService::dir();
        $base = (string) ($dir['baseurl'] ?? $dir['url'] ?? '');

        return rtrim($base, '/') . '/' . ltrim($file, '/');
    }

    /**
     * @return array<string, mixed>
     */
    public static function metadata(int $postId): array
    {
        $meta = MetaRepository::get('post', $postId, '_wp_attachment_metadata', true);

        return is_array($meta) ? $meta : [];
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function updateMetadata(int $postId, array $data): void
    {
        MetaRepository::update('post', $postId, '_wp_attachment_metadata', $data);
    }

    public static function caption(int $postId): string
    {
        $post = PostRepository::find($postId);

        return $post === null ? '' : $post->post_excerpt;
    }

    /**
     * @param array<string, mixed> $args
     */
    public static function insert(array $args): int
    {
        $args['post_type'] = 'attachment';

        return PostService::create($args);
    }

    public static function delete(int $postId, bool $forceDelete = false): bool
    {
        return PostRepository::delete($postId);
    }

    /**
     * @param array<string, mixed>|string $attr
     */
    public static function image(int $postId, string $size = 'thumbnail', bool $icon = false, array|string $attr = ''): string
    {
        $src = self::imageSrc($postId, $size, $icon);
        if ($src === false) {
            return '';
        }

        $alt = self::altText($postId);
        $class = 'attachment-' . $size . ' size-' . $size . ' wp-image-' . $postId;

        return sprintf(
            '<img width="%d" height="%d" src="%s" class="%s" alt="%s" />',
            (int) $src[1],
            (int) $src[2],
            htmlspecialchars((string) $src[0], ENT_QUOTES),
            htmlspecialchars($class, ENT_QUOTES),
            htmlspecialchars($alt, ENT_QUOTES),
        );
    }

    /**
     * @return array{0: string, 1: int, 2: int, 3: bool}|false
     */
    public static function imageSrc(int $postId, string $size = 'thumbnail', bool $icon = false): array|false
    {
        $url = self::url($postId);
        if ($url === false) {
            return false;
        }

        $meta = self::metadata($postId);
        $rawWidth = $meta['width'] ?? 0;
        $rawHeight = $meta['height'] ?? 0;
        $width = is_numeric($rawWidth) ? (int) $rawWidth : 0;
        $height = is_numeric($rawHeight) ? (int) $rawHeight : 0;

        return [$url, $width, $height, false];
    }

    public static function generateMetadata(int $postId): void
    {
    }

    public static function subsizes(int $postId): void
    {
    }

    public static function crop(string $src, int $srcX, int $srcY, int $srcW, int $srcH, int $dstW = 0, int $dstH = 0): PrestoError
    {
        return new PrestoError('not_supported', 'Image cropping is not supported.');
    }

    /**
     * @param array<string, mixed> $args
     */
    private static function argString(array $args, string $key, string $default = ''): string
    {
        $value = $args[$key] ?? $default;

        return is_scalar($value) ? (string) $value : $default;
    }

    private static function altText(int $postId): string
    {
        $alt = MetaRepository::get('post', $postId, '_wp_attachment_image_alt', true);

        return is_string($alt) ? $alt : '';
    }

    private static function resolveEmail(int|string $idOrEmail): string
    {
        if (is_string($idOrEmail) && str_contains($idOrEmail, '@')) {
            return $idOrEmail;
        }

        $user = UserRepository::find((int) $idOrEmail);

        return $user instanceof UserEntity ? $user->userEmail : '';
    }
}
