<?php

declare(strict_types=1);

/*
 * WordPress compatibility shims — common group (spec 10 §10.4 escaping /
 * options / formatting / url / users...). Delegate qua PrestoWorld\Core.
 *
 * Chỉ định nghĩa những hàm thường bị flag "unsupported/fallback" trong report
 * mà compiled code vẫn giữ nguyên lời gọi (mode s / x). Idempotent.
 */

use PrestoWorld\Core\AssetManager;
use PrestoWorld\Core\CacheRepository;
use PrestoWorld\Core\CapabilityService;
use PrestoWorld\Core\Escape;
use PrestoWorld\Core\Format;
use PrestoWorld\Core\JsonResponse;
use PrestoWorld\Core\Kses;
use PrestoWorld\Core\Nonce;
use PrestoWorld\Core\OptionRepository;
use PrestoWorld\Core\PluginInfo;
use PrestoWorld\Core\RedirectService;
use PrestoWorld\Core\Sanitize;
use PrestoWorld\Core\Security;
use PrestoWorld\Core\ThemeManager;
use PrestoWorld\Core\Translator;
use PrestoWorld\Core\Url;

if (!function_exists('home_url')) {
    function home_url(string $path = '', ?string $scheme = null): string
    {
        return \PrestoWorld\Core\Url::home($path, $scheme);
    }
}

if (!function_exists('site_url')) {
    function site_url(string $path = '', ?string $scheme = null): string
    {
        return \PrestoWorld\Core\Url::site($path, $scheme);
    }
}

if (!function_exists('admin_url')) {
    function admin_url(string $path = '', string $scheme = 'admin'): string
    {
        return \PrestoWorld\Core\Url::admin($path, $scheme);
    }
}

if (!function_exists('get_option')) {
    function get_option(string $option, mixed $default = false): mixed
    {
        return OptionRepository::get($option, $default);
    }
}

if (!function_exists('get_site_option')) {
    function get_site_option(string $option, mixed $default = false): mixed
    {
        return OptionRepository::getNetwork($option, $default);
    }
}

if (!function_exists('update_option')) {
    function update_option(string $option, mixed $value): bool
    {
        return OptionRepository::update($option, $value);
    }
}

if (!function_exists('update_site_option')) {
    function update_site_option(string $option, mixed $value): bool
    {
        return OptionRepository::updateNetwork($option, $value);
    }
}

if (!function_exists('add_option')) {
    function add_option(string $option, mixed $value = '', string $autoload = 'yes'): bool
    {
        return OptionRepository::add($option, $value);
    }
}

if (!function_exists('add_site_option')) {
    function add_site_option(string $option, mixed $value): bool
    {
        return OptionRepository::add($option, $value);
    }
}

if (!function_exists('delete_option')) {
    function delete_option(string $option): bool
    {
        return OptionRepository::delete($option);
    }
}

if (!function_exists('delete_site_option')) {
    function delete_site_option(string $option): bool
    {
        return OptionRepository::deleteNetwork($option);
    }
}

if (!function_exists('wp_cache_get')) {
    function wp_cache_get(mixed $key, string $group = '', mixed $default = false): mixed
    {
        return CacheRepository::get((string) $key, '', $default === false ? null : $default) ?? (CacheRepository::has((string) $key) ? CacheRepository::get((string) $key) : $default);
    }
}

if (!function_exists('wp_cache_set')) {
    function wp_cache_set(mixed $key, mixed $data, string $group = '', int $expire = 0): bool
    {
        CacheRepository::set((string) $key, $data, $expire);
        return true;
    }
}

if (!function_exists('wp_cache_delete')) {
    function wp_cache_delete(mixed $key, string $group = ''): bool
    {
        CacheRepository::delete((string) $key);
        return true;
    }
}

if (!function_exists('wp_cache_flush')) {
    function wp_cache_flush(): bool
    {
        CacheRepository::flush();
        return true;
    }
}

if (!function_exists('__')) {
    function __(string $text, string $domain = 'default'): string
    {
        return Translator::__($text, $domain);
    }
}

if (!function_exists('_e')) {
    function _e(string $text, string $domain = 'default'): void
    {
        echo Translator::__($text, $domain);
    }
}

if (!function_exists('_x')) {
    function _x(string $text, string $context, string $domain = 'default'): string
    {
        return Translator::withContext($text, $context, $domain);
    }
}

if (!function_exists('_n')) {
    function _n(string $single, string $plural, int $number, string $domain = 'default'): string
    {
        return Translator::_n($single, $plural, $number, $domain);
    }
}

if (!function_exists('esc_html')) {
    function esc_html(string $text): string
    {
        return Escape::html($text);
    }
}

if (!function_exists('esc_html__')) {
    function esc_html__(string $text, string $domain = 'default'): string
    {
        return Escape::html(Translator::__($text, $domain));
    }
}

if (!function_exists('esc_html_e')) {
    function esc_html_e(string $text, string $domain = 'default'): void
    {
        echo Escape::html(Translator::__($text, $domain));
    }
}

if (!function_exists('esc_attr')) {
    function esc_attr(string $text): string
    {
        return Escape::attr($text);
    }
}

if (!function_exists('esc_attr_e')) {
    function esc_attr_e(string $text, string $domain = 'default'): void
    {
        echo Escape::attr(Translator::__($text, $domain));
    }
}

if (!function_exists('esc_url')) {
    function esc_url(string $url, string $protocols = '', array $_context = []): string
    {
        return Escape::url($url);
    }
}

if (!function_exists('esc_url_raw')) {
    function esc_url_raw(string $url, string $protocols = ''): string
    {
        return Escape::urlRaw($url);
    }
}

if (!function_exists('esc_js')) {
    function esc_js(string $text): string
    {
        return Escape::js($text);
    }
}

if (!function_exists('esc_textarea')) {
    function esc_textarea(string $text): string
    {
        return Escape::textarea($text);
    }
}

if (!function_exists('sanitize_text_field')) {
    function sanitize_text_field(string $text): string
    {
        return Sanitize::text($text);
    }
}

if (!function_exists('sanitize_textarea_field')) {
    function sanitize_textarea_field(string $text): string
    {
        return Sanitize::textarea($text);
    }
}

if (!function_exists('sanitize_title')) {
    function sanitize_title(string $title, string $_fallback = '', string $_context = ''): string
    {
        return Sanitize::title($title);
    }
}

if (!function_exists('sanitize_email')) {
    function sanitize_email(string $email): string
    {
        return Sanitize::email($email);
    }
}

if (!function_exists('sanitize_key')) {
    function sanitize_key(string $key): string
    {
        return Sanitize::key($key);
    }
}

if (!function_exists('sanitize_file_name')) {
    function sanitize_file_name(string $file): string
    {
        return Sanitize::fileName($file);
    }
}

if (!function_exists('sanitize_mime_type')) {
    function sanitize_mime_type(string $mime): string
    {
        return Sanitize::mimeType($mime);
    }
}

if (!function_exists('wp_kses')) {
    function wp_kses(string $content, array|string $allowed, array $protocols = []): string
    {
        return Kses::filter($content, is_array($allowed) ? $allowed : []);
    }
}

if (!function_exists('wp_kses_post')) {
    function wp_kses_post(string $content): string
    {
        return Kses::post($content);
    }
}

if (!function_exists('wp_json_encode')) {
    function wp_json_encode(mixed $value, int $flags = 0, int $depth = 512): string|false
    {
        return json_encode($value, $flags, $depth);
    }
}

if (!function_exists('maybe_serialize')) {
    function maybe_serialize(mixed $data): string
    {
        return Format::serialize($data);
    }
}

if (!function_exists('maybe_unserialize')) {
    function maybe_unserialize(string $data): mixed
    {
        return Format::unserialize($data);
    }
}

if (!function_exists('maybe_unserialize_data')) {
    function maybe_unserialize_data(string $data): mixed
    {
        return Format::unserialize($data);
    }
}

if (!function_exists('wp_nonce_field')) {
    function wp_nonce_field(string $action = '-1', string $name = '_wpnonce', bool $referer = true, bool $echo = true): string
    {
        $field = Nonce::field($action, $name);
        if ($echo) {
            echo $field;
            return '';
        }

        return $field;
    }
}

if (!function_exists('wp_create_nonce')) {
    function wp_create_nonce(string $action = '-1'): string
    {
        return Nonce::create($action);
    }
}

if (!function_exists('wp_verify_nonce')) {
    function wp_verify_nonce(string $nonce, string $action = '-1'): int|false
    {
        return Nonce::verify($nonce, $action) ? 1 : false;
    }
}

if (!function_exists('wp_nonce_url')) {
    function wp_nonce_url(string $actionurl, string $action = '-1', string $name = '_wpnonce'): string
    {
        return Nonce::url($actionurl, $action, $name);
    }
}

if (!function_exists('check_admin_referer')) {
    function check_admin_referer(string $action = '-1', string $queryArg = '_wpnonce'): int|false
    {
        return Nonce::checkAdmin($action) ? 1 : false;
    }
}

if (!function_exists('check_ajax_referer')) {
    function check_ajax_referer(string $action = '-1', string|false $queryArg = false): int|false
    {
        return Nonce::checkAjax($action) ? 1 : false;
    }
}

if (!function_exists('wp_redirect')) {
    function wp_redirect(string $location, int $status = 302, string $xRedirectBy = ''): bool
    {
        return RedirectService::to($location, $status);
    }
}

if (!function_exists('wp_safe_redirect')) {
    function wp_safe_redirect(string $location, int $status = 302, string $xRedirectBy = ''): bool
    {
        return RedirectService::safe($location, $status);
    }
}

if (!function_exists('wp_send_json')) {
    function wp_send_json(mixed $response, int $statusCode = 200): never
    {
        JsonResponse::send($response, $statusCode);
    }
}

if (!function_exists('wp_send_json_success')) {
    function wp_send_json_success(mixed $data = null, int $statusCode = null): never
    {
        JsonResponse::success($data, $statusCode);
    }
}

if (!function_exists('wp_send_json_error')) {
    function wp_send_json_error(mixed $data = null, int $statusCode = null): never
    {
        JsonResponse::error($data, $statusCode);
    }
}

if (!function_exists('add_theme_support')) {
    function add_theme_support(string $feature, mixed ...$args): void
    {
        ThemeManager::addSupport($feature, $args);
    }
}

if (!function_exists('remove_theme_support')) {
    function remove_theme_support(string $feature): bool
    {
        return ThemeManager::removeSupport($feature);
    }
}

if (!function_exists('current_theme_supports')) {
    function current_theme_supports(string $feature): bool
    {
        return ThemeManager::supports($feature);
    }
}

if (!function_exists('add_editor_style')) {
    function add_editor_style(mixed $stylesheet = 'editor-style.css'): void
    {
        AssetManager::enqueueStyle('editor', (string) $stylesheet);
    }
}

if (!function_exists('wp_enqueue_style')) {
    function wp_enqueue_style(string $handle, string $src = '', array $deps = [], string|bool|null $ver = false, string $media = 'all'): void
    {
        AssetManager::enqueueStyle($handle, $src);
    }
}

if (!function_exists('wp_enqueue_script')) {
    function wp_enqueue_script(string $handle, string $src = '', array $deps = [], string|bool|null $ver = false, bool $inFooter = false): void
    {
        AssetManager::enqueueScript($handle, $src);
    }
}

if (!function_exists('wp_localize_script')) {
    function wp_localize_script(string $handle, string $objectName, array $data): void
    {
        AssetManager::localize($handle, $objectName, $data);
    }
}

if (!function_exists('current_user_can')) {
    function current_user_can(string $capability, mixed ...$args): bool
    {
        return CapabilityService::userCan($capability);
    }
}

if (!function_exists('is_user_logged_in')) {
    function is_user_logged_in(): bool
    {
        return CapabilityService::isLoggedIn();
    }
}

if (!function_exists('is_admin')) {
    function is_admin(): bool
    {
        return \PrestoWorld\Core\Request::isAdmin();
    }
}

if (!function_exists('is_front_page')) {
    function is_front_page(): bool
    {
        return \PrestoWorld\Core\QueryState::isFrontPage();
    }
}

if (!function_exists('is_home')) {
    function is_home(): bool
    {
        return \PrestoWorld\Core\QueryState::isHome();
    }
}

if (!function_exists('is_single')) {
    function is_single(): bool
    {
        return \PrestoWorld\Core\QueryState::isSingle();
    }
}

if (!function_exists('is_page')) {
    function is_page(): bool
    {
        return \PrestoWorld\Core\QueryState::isPage();
    }
}

if (!function_exists('is_archive')) {
    function is_archive(): bool
    {
        return \PrestoWorld\Core\QueryState::isArchive();
    }
}

if (!function_exists('is_search')) {
    function is_search(): bool
    {
        return \PrestoWorld\Core\QueryState::isSearch();
    }
}

if (!function_exists('is_404')) {
    function is_404(): bool
    {
        return \PrestoWorld\Core\QueryState::is404();
    }
}

if (!function_exists('get_bloginfo')) {
    function get_bloginfo(string $show = ''): string
    {
        return (string) \PrestoWorld\Core\SiteInfo::get($show);
    }
}

if (!function_exists('wp_upload_dir')) {
    function wp_upload_dir(string $time = ''): array
    {
        return \PrestoWorld\Core\UploadService::dir();
    }
}

if (!function_exists('wp_mail')) {
    /**
     * @param string|array<int, string> $to
     * @param string|array<int, string> $headers
     * @param string|array<int, string> $attachments
     */
    function wp_mail(string|array $to, string $subject, string $message, string|array $headers = '', string|array $attachments = []): bool
    {
        $recipients = is_array($to) ? array_values($to) : [$to];

        return \PrestoWorld\Core\MailService::send(array_map('strval', $recipients), $subject, $message, is_array($headers) ? '' : (string) $headers);
    }
}

if (!function_exists('wp_get_theme')) {
    function wp_get_theme(string $stylesheet = ''): \PrestoWorld\Core\Theme\ThemeEntity
    {
        return ThemeManager::get($stylesheet);
    }
}

if (!function_exists('get_template_directory')) {
    function get_template_directory(): string
    {
        return ThemeManager::directory();
    }
}

if (!function_exists('get_stylesheet_directory')) {
    function get_stylesheet_directory(): string
    {
        return ThemeManager::stylesheetDirectory();
    }
}

if (!function_exists('get_template_directory_uri')) {
    function get_template_directory_uri(): string
    {
        return ThemeManager::templateUri();
    }
}

if (!function_exists('get_stylesheet_directory_uri')) {
    function get_stylesheet_directory_uri(): string
    {
        return ThemeManager::stylesheetUri();
    }
}

if (!function_exists('get_stylesheet')) {
    function get_stylesheet(): string
    {
        return ThemeManager::stylesheet();
    }
}

if (!function_exists('get_template')) {
    function get_template(): string
    {
        return ThemeManager::template();
    }
}

if (!function_exists('wp_create_user')) {
    function wp_create_user(string $username, string $password, string $email = ''): int|\PrestoWorld\Core\Error\PrestoError
    {
        return \PrestoWorld\Core\UserService::create($username, $password, $email);
    }
}

if (!function_exists('wp_insert_user')) {
    function wp_insert_user(array $userdata): int|\PrestoWorld\Core\Error\PrestoError
    {
        return \PrestoWorld\Core\UserService::insert($userdata);
    }
}

if (!function_exists('wp_update_user')) {
    function wp_update_user(array $userdata): int|\PrestoWorld\Core\Error\PrestoError
    {
        return \PrestoWorld\Core\UserService::update($userdata);
    }
}

if (!function_exists('wp_delete_user')) {
    function wp_delete_user(int $id, ?int $reassign = null): bool
    {
        return \PrestoWorld\Core\UserService::delete($id, $reassign);
    }
}

if (!function_exists('get_userdata')) {
    function get_userdata(int $userId): \PrestoWorld\Core\User\UserEntity|false
    {
        return \PrestoWorld\Core\UserRepository::find($userId);
    }
}

if (!function_exists('get_current_user_id')) {
    function get_current_user_id(): int
    {
        return \PrestoWorld\Core\AuthService::currentUserId();
    }
}

if (!function_exists('number_format_i18n')) {
    function number_format_i18n(float $number, int $decimals = 0): string
    {
        return Format::number($number, $decimals);
    }
}

if (!function_exists('wp_timezone_string')) {
    function wp_timezone_string(): string
    {
        return \PrestoWorld\Core\Clock::timezone();
    }
}

if (!function_exists('current_time')) {
    function current_time(string $type = 'timestamp', int $gmt = 0): string|int
    {
        return \PrestoWorld\Core\Clock::current($type, (bool) $gmt);
    }
}

if (!function_exists('wp_nonce_map')) {
    function wp_nonce_map(): array
    {
        return [];
    }
}

if (!function_exists('wp_remote_get')) {
    function wp_remote_get(string $url, array $args = []): array|false
    {
        return \PrestoWorld\Core\HttpService::download($url);
    }
}

if (!function_exists('wp_strip_all_tags')) {
    function wp_strip_all_tags(string $text, bool $removeBreaks = false): string
    {
        return Sanitize::stripTags($text, $removeBreaks);
    }
}

if (!function_exists('wp_check_invalid_utf8')) {
    function wp_check_invalid_utf8(string $text, bool $strip = false): string
    {
        return Sanitize::checkUtf8($text, $strip);
    }
}

if (!function_exists('absint')) {
    function absint(mixed $maybeint): int
    {
        return Sanitize::absInt($maybeint);
    }
}

if (!function_exists('wp_rand')) {
    function wp_rand(int $min = 0, int $max = PHP_INT_MAX): int
    {
        return Security::rand($min, $max);
    }
}

if (!function_exists('wp_generate_password')) {
    function wp_generate_password(int $length = 12, bool $specialChars = true, bool $extraSpecialChars = false): string
    {
        return Security::generatePassword($length, $specialChars);
    }
}