<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\ContextBuilder\Gutenberg;

use PrestoWorld\Core\AssetManager;
use PrestoWorld\Core\Nonce;

/**
 * GutenbergIntegration — integrates Gutenberg fork into PrestoWorld.
 *
 * Enqueues Gutenberg editor assets and provides the data/config
 * that Gutenberg needs to work with PrestoWorld backend.
 */
class GutenbergIntegration
{
    /**
     * Enqueue Gutenberg editor assets.
     *
     * Called when loading the post editor screen.
     */
    public static function enqueueEditorAssets(): void
    {
        // Enqueue Gutenberg editor JS
        AssetManager::enqueueScript(
            'pw-gutenberg-editor',
            '/assets/gutenberg/editor.js',
            ['pw-gutenberg-blocks', 'pw-gutenberg-block-editor', 'pw-gutenberg-components', 'pw-gutenberg-data'],
            '24.2.0-presto',
            true
        );

        // Enqueue Gutenberg editor CSS
        AssetManager::enqueueStyle(
            'pw-gutenberg-editor',
            '/assets/gutenberg/editor.css',
            ['wp-edit-blocks'],
            '24.2.0-presto'
        );

        // Enqueue block library
        AssetManager::enqueueScript(
            'pw-gutenberg-blocks',
            '/assets/gutenberg/blocks.js',
            [],
            '24.2.0-presto',
            true
        );

        // Enqueue block editor
        AssetManager::enqueueScript(
            'pw-gutenberg-block-editor',
            '/assets/gutenberg/block-editor.js',
            ['pw-gutenberg-blocks', 'pw-gutenberg-components', 'pw-gutenberg-data'],
            '24.2.0-presto',
            true
        );

        // Enqueue components
        AssetManager::enqueueScript(
            'pw-gutenberg-components',
            '/assets/gutenberg/components.js',
            ['pw-gutenberg-data', 'pw-gutenberg-element'],
            '24.2.0-presto',
            true
        );

        // Enqueue data layer
        AssetManager::enqueueScript(
            'pw-gutenberg-data',
            '/assets/gutenberg/data.js',
            [],
            '24.2.0-presto',
            true
        );

        // Localize script with PrestoWorld config
        wp_localize_script('pw-gutenberg-editor', 'prestoWorldConfig', self::getEditorConfig());
        wp_localize_script('pw-gutenberg-editor', 'prestoWorldNonce', self::getNonce());
    }

    /**
     * Get editor configuration for Gutenberg.
     *
     * @return array<string, mixed>
     */
    public static function getEditorConfig(): array
    {
        $postId = get_the_ID();
        $post = get_post($postId);

        return [
            'postId' => $postId,
            'postType' => $post ? $post->post_type : 'post',
            'postTitle' => $post ? $post->post_title : '',
            'postContent' => $post ? $post->post_content : '',
            'postExcerpt' => $post ? $post->post_excerpt : '',
            'apiPrefix' => '/pw-api/v1',
            'theme' => \PrestoWorld\Core\ThemeManager::stylesheet(),
            'themeStyles' => self::getThemeStyles(),
            'user' => self::getCurrentUser(),
            'userCan' => self::getUserCapabilities(),
            'availableTemplates' => self::getAvailableTemplates(),
            'availableBlockPatterns' => self::getAvailableBlockPatterns(),
            'supportsTemplates' => true,
            'supportsTemplateParts' => true,
            'supportsBlockPatterns' => true,
            'mediaUpload' => self::getMediaUploadConfig(),
            'mediaLibrary' => self::getMediaLibraryConfig(),
        ];
    }

    /**
     * Get nonce for API requests.
     */
    public static function getNonce(): string
    {
        return Nonce::create('pw-gutenberg');
    }

    /**
     * Get theme styles for Gutenberg editor.
     */
    protected static function getThemeStyles(): string
    {
        $themeDir = \PrestoWorld\Core\ThemeManager::stylesheetDirectory();
        $stylePath = $themeDir . '/style.css';

        if (is_file($stylePath)) {
            $css = file_get_contents($stylePath);
            return $css ?: '';
        }

        return '';
    }

    /**
     * Get current user data.
     *
     * @return array<string, mixed>
     */
    protected static function getCurrentUser(): array
    {
        $user = wp_get_current_user();
        if ($user->ID === 0) {
            return [];
        }

        return [
            'id' => $user->ID,
            'name' => $user->display_name,
            'email' => $user->user_email,
            'roles' => $user->roles,
        ];
    }

    /**
     * Get user capabilities.
     *
     * @return array<string, bool>
     */
    protected static function getUserCapabilities(): array
    {
        $user = wp_get_current_user();
        if ($user->ID === 0) {
            return [];
        }

        return [
            'edit_posts' => $user->has_cap('edit_posts'),
            'edit_pages' => $user->has_cap('edit_pages'),
            'publish_posts' => $user->has_cap('publish_posts'),
            'edit_theme_options' => $user->has_cap('edit_theme_options'),
            'upload_files' => $user->has_cap('upload_files'),
        ];
    }

    /**
     * Get available templates from theme.
     *
     * @return array<int, array<string, mixed>>
     */
    protected static function getAvailableTemplates(): array
    {
        $themeDir = \PrestoWorld\Core\ThemeManager::stylesheetDirectory();
        $templatesDir = $themeDir . '/templates';

        $templates = [];
        if (is_dir($templatesDir)) {
            $files = scandir($templatesDir);
            foreach ($files as $file) {
                if ($file === '.' || $file === '..') {
                    continue;
                }
                if (pathinfo($file, PATHINFO_EXTENSION) === 'html') {
                    $name = pathinfo($file, PATHINFO_FILENAME);
                    $templates[] = [
                        'slug' => $name,
                        'title' => ucwords(str_replace('-', ' ', $name)),
                        'file' => $file,
                    ];
                }
            }
        }

        return $templates;
    }

    /**
     * Get available block patterns.
     *
     * @return array<int, array<string, mixed>>
     */
    protected static function getAvailableBlockPatterns(): array
    {
        // In real implementation, this would scan patterns directory
        return [];
    }

    /**
     * Get media upload configuration.
     *
     * @return array<string, mixed>
     */
    protected static function getMediaUploadConfig(): array
    {
        return [
            'maxUploadSize' => wp_max_upload_size(),
            'allowedMimeTypes' => get_allowed_mime_types(),
            'uploadUrl' => '/pw-api/v1/media/upload',
        ];
    }

    /**
     * Get media library configuration.
     *
     * @return array<string, mixed>
     */
    protected static function getMediaLibraryConfig(): array
    {
        return [
            'listUrl' => '/pw-api/v1/media',
            'searchUrl' => '/pw-api/v1/media/search',
            'uploadUrl' => '/pw-api/v1/media/upload',
        ];
    }
}