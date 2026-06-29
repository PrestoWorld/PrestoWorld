<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use Witals\Framework\Http\Request;
use PrestoWorld\Foundation\Database\OptionRepository;

class SettingsController
{
    public function __construct(
        private OptionRepository $options,
    ) {}

    public function saveGeneral(Request $request): void
    {
        $this->options->set('blogname', $request->post('blogname', ''));
        $this->options->set('blogdescription', $request->post('blogdescription', ''));
        $this->options->set('admin_email', $request->post('admin_email', ''));
        $this->options->set('default_role', $request->post('default_role', 'subscriber'));
        $this->options->set('timezone_string', $request->post('timezone_string', ''));
        $this->options->set('date_format', $request->post('date_format', 'F j, Y'));
        $this->options->set('time_format', $request->post('time_format', 'g:i a'));
        $this->options->set('start_of_week', $request->post('start_of_week', '0'));
        $this->options->set('WPLANG', $request->post('language', ''));

        $this->redirectWithNotice('/wp-admin/options-general.php', 'Settings saved.');
    }

    public function saveWriting(Request $request): void
    {
        $this->options->set('default_category', $request->post('default_category', '1'));
        $this->options->set('default_post_format', $request->post('default_post_format', '0'));

        $this->redirectWithNotice('/wp-admin/options-writing.php', 'Settings saved.');
    }

    public function saveReading(Request $request): void
    {
        $this->options->set('show_on_front', $request->post('show_on_front', 'posts'));
        $this->options->set('page_on_front', $request->post('page_on_front', '0'));
        $this->options->set('page_for_posts', $request->post('page_for_posts', '0'));
        $this->options->set('posts_per_page', $request->post('posts_per_page', '10'));
        $this->options->set('rss_use_excerpt', $request->post('feed_use_excerpt', '0'));
        $this->options->set('blog_public', $request->post('blog_public', '0') ? '0' : '1');

        $this->redirectWithNotice('/wp-admin/options-reading.php', 'Settings saved.');
    }

    public function saveDiscussion(Request $request): void
    {
        $this->options->set('default_pingback_flag', $request->post('default_pingback_flag', '0') ? '1' : '0');
        $this->options->set('default_ping_status', $request->post('default_ping_status', 'open'));
        $this->options->set('default_comment_status', $request->post('default_comment_status', 'open'));
        $this->options->set('require_name_email', $request->post('require_name_email', '0') ? '1' : '0');
        $this->options->set('comment_registration', $request->post('comment_registration', '0') ? '1' : '0');
        $this->options->set('close_comments_for_old_posts', $request->post('close_comments_for_old_posts', '0') ? '1' : '0');
        $this->options->set('close_comments_days_old', $request->post('close_comments_days_old', '14'));
        $this->options->set('thread_comments', $request->post('thread_comments', '0') ? '1' : '0');
        $this->options->set('thread_comments_depth', $request->post('thread_comments_depth', '5'));
        $this->options->set('page_comments', $request->post('page_comments', '0') ? '1' : '0');
        $this->options->set('comments_per_page', $request->post('comments_per_page', '50'));
        $this->options->set('default_comments_page', $request->post('default_comments_page', 'newest'));
        $this->options->set('comment_order', $request->post('comment_order', 'asc'));
        $this->options->set('comments_notify', $request->post('comments_notify', '0') ? '1' : '0');
        $this->options->set('moderation_notify', $request->post('moderation_notify', '0') ? '1' : '0');
        $this->options->set('comment_moderation', $request->post('comment_moderation', '0') ? '1' : '0');
        $this->options->set('comment_max_links', $request->post('comment_max_links', '2'));
        $this->options->set('moderation_keys', $request->post('moderation_keys', ''));
        $this->options->set('disallowed_keys', $request->post('disallowed_keys', ''));
        $this->options->set('comment_previously_approved', $request->post('comment_previously_approved', '0') ? '1' : '0');
        $this->options->set('show_avatars', $request->post('show_avatars', '0') ? '1' : '0');
        $this->options->set('avatar_rating', $request->post('avatar_rating', 'G'));
        $this->options->set('avatar_default', $request->post('avatar_default', 'mystery'));

        $this->redirectWithNotice('/wp-admin/options-discussion.php', 'Settings saved.');
    }

    public function saveMedia(Request $request): void
    {
        $this->options->set('thumbnail_size_w', $request->post('thumbnail_size_w', '150'));
        $this->options->set('thumbnail_size_h', $request->post('thumbnail_size_h', '150'));
        $this->options->set('medium_size_w', $request->post('medium_size_w', '300'));
        $this->options->set('medium_size_h', $request->post('medium_size_h', '300'));
        $this->options->set('large_size_w', $request->post('large_size_w', '1024'));
        $this->options->set('large_size_h', $request->post('large_size_h', '1024'));
        $this->options->set('uploads_use_yearmonth_folders', $request->post('uploads_use_yearmonth_folders', '0') ? '1' : '0');

        $this->redirectWithNotice('/wp-admin/options-media.php', 'Settings saved.');
    }

    public function savePermalink(Request $request): void
    {
        $permalink = $request->post('permalink_structure', '');
        if ($permalink === 'custom') {
            $permalink = $request->post('permalink_structure_custom', '/%postname%/');
        }
        $this->options->set('permalink_structure', $permalink);
        $this->options->set('category_base', $request->post('category_base', ''));
        $this->options->set('tag_base', $request->post('tag_base', ''));

        $this->redirectWithNotice('/wp-admin/options-permalink.php', 'Settings saved.');
    }

    public function savePrivacy(Request $request): void
    {
        $this->options->set('wp_page_for_privacy_policy', $request->post('wp_page_for_privacy_policy', '0'));

        $this->redirectWithNotice('/wp-admin/options-privacy.php', 'Settings saved.');
    }

    private function redirectWithNotice(string $url, string $message): never
    {
        $separator = str_contains($url, '?') ? '&' : '?';
        header('Location: ' . $url . $separator . 'settings_saved=1');
        exit;
    }
}
