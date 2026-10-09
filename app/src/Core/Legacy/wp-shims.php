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

/* ---------------------------------------------------------------------------
 * Group 1: Escaping & Sanitization (10.4.2) — bổ sung
 * ------------------------------------------------------------------------- */

if (!function_exists('esc_attr__')) {
    function esc_attr__(string $text, string $domain = 'default'): string
    {
        return Escape::attr(Translator::__($text, $domain));
    }
}

if (!function_exists('esc_xml')) {
    function esc_xml(string $text): string
    {
        return Escape::xml($text);
    }
}

if (!function_exists('wp_kses_allowed_html')) {
    /** @return array<string, mixed> */
    function wp_kses_allowed_html(string $context = 'post'): array
    {
        return Kses::allowedHtml($context);
    }
}

if (!function_exists('sanitize_user')) {
    function sanitize_user(string $username, bool $strict = false): string
    {
        return Sanitize::user($username, $strict);
    }
}

if (!function_exists('sanitize_html_class')) {
    function sanitize_html_class(string $class, string $fallback = ''): string
    {
        return Sanitize::htmlClass($class, $fallback);
    }
}

if (!function_exists('_wp_specialchars')) {
    function _wp_specialchars(string $text, int $quoteStyle = ENT_NOQUOTES, string $charset = 'UTF-8', bool $doubleEncode = false): string
    {
        return Escape::specialChars($text, $quoteStyle, $charset, $doubleEncode);
    }
}

/* ---------------------------------------------------------------------------
 * Group 3: Formatting, Text & Dates (10.4.4) — bổ sung
 * ------------------------------------------------------------------------- */

if (!function_exists('date_i18n')) {
    function date_i18n(string $format, int|bool $timestampWithOffset = false, bool $gmt = false): string
    {
        $timestamp = is_int($timestampWithOffset) ? $timestampWithOffset : \PrestoWorld\Core\Clock::now();

        return Format::date($timestamp, $format);
    }
}

if (!function_exists('mysql2date')) {
    function mysql2date(string $format, string $date, bool $translate = true): string
    {
        $timestamp = strtotime($date);

        return $timestamp === false ? $date : Format::date($timestamp, $format);
    }
}

if (!function_exists('human_time_diff')) {
    function human_time_diff(int $from, int $to = 0): string
    {
        return Format::humanTimeDiff($from, $to === 0 ? null : $to);
    }
}

if (!function_exists('get_gmt_from_date')) {
    function get_gmt_from_date(string $date, string $format = 'Y-m-d H:i:s'): string
    {
        $gmt = Format::toGmt($date);

        return $format === 'Y-m-d H:i:s' ? $gmt : date($format, strtotime($gmt) ?: time());
    }
}

if (!function_exists('get_date_from_gmt')) {
    function get_date_from_gmt(string $date, string $format = 'Y-m-d H:i:s'): string
    {
        $local = Format::fromGmt($date);

        return $format === 'Y-m-d H:i:s' ? $local : date($format, strtotime($local) ?: time());
    }
}

if (!function_exists('wp_date')) {
    function wp_date(string $format, int|false|null $timestamp = null, ?\DateTimeZone $timezone = null): string
    {
        return \PrestoWorld\Core\Clock::date($timestamp === false ? null : $timestamp, $format);
    }
}

if (!function_exists('current_datetime')) {
    function current_datetime(): \DateTimeImmutable
    {
        return (new \DateTimeImmutable('now', new \DateTimeZone(\PrestoWorld\Core\Clock::timezone())));
    }
}

if (!function_exists('wp_timezone')) {
    function wp_timezone(): \DateTimeZone
    {
        return new \DateTimeZone(\PrestoWorld\Core\Clock::timezone());
    }
}

if (!function_exists('wp_timezone_offset')) {
    function wp_timezone_offset(): float
    {
        return \PrestoWorld\Core\Clock::offset();
    }
}

if (!function_exists('size_format')) {
    function size_format(int|float $bytes, int $decimals = 0): string
    {
        return Format::size($bytes, $decimals);
    }
}

if (!function_exists('wp_html_excerpt')) {
    function wp_html_excerpt(string $text, int $count, string $more = ''): string
    {
        return Format::htmlExcerpt($text, $count, $more);
    }
}

if (!function_exists('wp_trim_words')) {
    function wp_trim_words(string $text, int $numWords = 55, ?string $more = null): string
    {
        return Format::trimWords($text, $numWords, $more ?? '&hellip;');
    }
}

if (!function_exists('trailingslashit')) {
    function trailingslashit(string $value): string
    {
        return Format::trailingslash($value);
    }
}

if (!function_exists('untrailingslashit')) {
    function untrailingslashit(string $value): string
    {
        return Format::untrailingslash($value);
    }
}

if (!function_exists('wp_basename')) {
    function wp_basename(string $path, string $suffix = ''): string
    {
        return Format::basename($path, $suffix);
    }
}

if (!function_exists('wp_normalize_path')) {
    function wp_normalize_path(string $path): string
    {
        return Format::normalizePath($path);
    }
}

if (!function_exists('path_join')) {
    function path_join(string $base, string $path): string
    {
        return Format::pathJoin($base, $path);
    }
}

if (!function_exists('is_serialized')) {
    function is_serialized(mixed $data, bool $strict = true): bool
    {
        return is_string($data) && Format::isSerialized($data);
    }
}

if (!function_exists('strip_shortcodes')) {
    function strip_shortcodes(string $content): string
    {
        return \PrestoWorld\Core\Shortcode::strip($content);
    }
}

if (!function_exists('do_shortcode')) {
    function do_shortcode(string $content): string
    {
        return \PrestoWorld\Core\Shortcode::render($content);
    }
}

if (!function_exists('has_shortcode')) {
    function has_shortcode(string $content, string $tag): bool
    {
        return \PrestoWorld\Core\Shortcode::has($content, $tag);
    }
}

if (!function_exists('shortcode_atts')) {
    /**
     * @param array<string, mixed> $pairs
     * @param array<string, mixed> $atts
     * @return array<string, mixed>
     */
    function shortcode_atts(array $pairs, array $atts, string $shortcode = ''): array
    {
        $out = array_intersect_key($atts, $pairs);

        return array_merge($pairs, $out);
    }
}

if (!function_exists('wptexturize')) {
    function wptexturize(string $text): string
    {
        return Format::texturize($text);
    }
}

if (!function_exists('wpautop')) {
    function wpautop(string $text, bool $br = true): string
    {
        return Format::autop($text);
    }
}

if (!function_exists('convert_smilies')) {
    function convert_smilies(string $text): string
    {
        return Format::smilies($text);
    }
}

/* ---------------------------------------------------------------------------
 * Group 4: Options & Transients (10.4.5) — bổ sung
 * ------------------------------------------------------------------------- */

if (!function_exists('wp_load_alloptions')) {
    /** @return array<string, mixed> */
    function wp_load_alloptions(bool $forceCache = false): array
    {
        return OptionRepository::loadAll();
    }
}

if (!function_exists('set_transient')) {
    function set_transient(string $key, mixed $value, int $expiration = 0): bool
    {
        return CacheRepository::set('_transient_' . $key, $value, $expiration, 'transient');
    }
}

if (!function_exists('get_transient')) {
    function get_transient(string $key): mixed
    {
        return CacheRepository::get('_transient_' . $key, 'transient');
    }
}

if (!function_exists('delete_transient')) {
    function delete_transient(string $key): bool
    {
        return CacheRepository::delete('_transient_' . $key, 'transient');
    }
}

if (!function_exists('set_site_transient')) {
    function set_site_transient(string $key, mixed $value, int $expiration = 0): bool
    {
        return CacheRepository::set('_site_transient_' . $key, $value, $expiration, 'transient');
    }
}

if (!function_exists('get_site_transient')) {
    function get_site_transient(string $key): mixed
    {
        return CacheRepository::get('_site_transient_' . $key, 'transient');
    }
}

if (!function_exists('delete_site_transient')) {
    function delete_site_transient(string $key): bool
    {
        return CacheRepository::delete('_site_transient_' . $key, 'transient');
    }
}

if (!function_exists('wp_cache_incr')) {
    function wp_cache_incr(string|int $key, int $offset = 1, string $group = ''): int|false
    {
        return CacheRepository::increment((string) $key, $offset, $group);
    }
}

if (!function_exists('wp_using_ext_object_cache')) {
    function wp_using_ext_object_cache(?bool $using = null): bool
    {
        return CacheRepository::usingExternal();
    }
}

/* ---------------------------------------------------------------------------
 * Group 7: URLs & Redirects (10.4.8) — bổ sung
 * ------------------------------------------------------------------------- */

if (!function_exists('self_admin_url')) {
    function self_admin_url(string $path = '', string $scheme = 'admin'): string
    {
        return Url::selfAdmin($path);
    }
}

if (!function_exists('content_url')) {
    function content_url(string $path = ''): string
    {
        return Url::content($path);
    }
}

if (!function_exists('includes_url')) {
    function includes_url(string $path = '', ?string $scheme = null): string
    {
        return Url::includes($path);
    }
}

if (!function_exists('get_home_path')) {
    function get_home_path(): string
    {
        return rtrim(\PrestoWorld\Core\Path::home(), '/\\') . '/';
    }
}

if (!function_exists('get_permalink')) {
    function get_permalink(int|object $post = 0, bool $leavename = false): string
    {
        return Url::permalink(is_object($post) ? (int) ($post->ID ?? 0) : $post);
    }
}

if (!function_exists('get_attachment_link')) {
    function get_attachment_link(int|object $post = 0): string
    {
        return Url::attachment(is_object($post) ? (int) ($post->ID ?? 0) : $post);
    }
}

if (!function_exists('get_author_posts_url')) {
    function get_author_posts_url(int $authorId, string $authorNicename = ''): string
    {
        return Url::authorArchive($authorId, $authorNicename);
    }
}

if (!function_exists('get_category_link')) {
    function get_category_link(int|object $category): string
    {
        return Url::category(is_object($category) ? (int) ($category->term_id ?? 0) : $category);
    }
}

if (!function_exists('get_tag_link')) {
    function get_tag_link(int|object $tag): string
    {
        return Url::tag(is_object($tag) ? (int) ($tag->term_id ?? 0) : $tag);
    }
}

if (!function_exists('get_term_link')) {
    /** @param int|object|array<string, mixed> $term */
    function get_term_link(int|object|array $term, string $taxonomy = ''): string
    {
        if (is_object($term)) {
            return Url::term($taxonomy !== '' ? $taxonomy : (string) ($term->taxonomy ?? ''), (int) ($term->term_id ?? 0), (string) ($term->slug ?? ''));
        }
        if (is_array($term)) {
            $termId = $term['term_id'] ?? 0;
            $slug = $term['slug'] ?? '';
            $tax = $taxonomy !== '' ? $taxonomy : ($term['taxonomy'] ?? '');

            return Url::term(
                is_string($tax) ? $tax : '',
                is_numeric($termId) ? (int) $termId : 0,
                is_string($slug) ? $slug : '',
            );
        }

        return Url::term($taxonomy, $term);
    }
}

if (!function_exists('get_search_link')) {
    function get_search_link(string $query = ''): string
    {
        return Url::search($query);
    }
}

if (!function_exists('get_post_type_archive_link')) {
    function get_post_type_archive_link(string $postType): string|false
    {
        $url = Url::postTypeArchive($postType);

        return $url === '' ? false : $url;
    }
}

if (!function_exists('wp_login_url')) {
    function wp_login_url(string $redirect = '', bool $forceReauth = false): string
    {
        return Url::login($redirect);
    }
}

if (!function_exists('wp_logout_url')) {
    function wp_logout_url(string $redirect = ''): string
    {
        return Url::logout($redirect);
    }
}

if (!function_exists('wp_registration_url')) {
    function wp_registration_url(): string
    {
        return Url::register();
    }
}

if (!function_exists('wp_lostpassword_url')) {
    function wp_lostpassword_url(string $redirect = ''): string
    {
        return Url::lostPassword($redirect);
    }
}

if (!function_exists('set_url_scheme')) {
    function set_url_scheme(string $url, ?string $scheme = null): string
    {
        return Url::setScheme($url, $scheme);
    }
}

if (!function_exists('wp_sanitize_redirect')) {
    function wp_sanitize_redirect(string $location): string
    {
        return RedirectService::sanitize($location);
    }
}

if (!function_exists('nocache_headers')) {
    function nocache_headers(): void
    {
        \PrestoWorld\Core\Response::noCache();
    }
}

if (!function_exists('status_header')) {
    function status_header(int $code, string $description = ''): void
    {
        \PrestoWorld\Core\Response::status($code);
    }
}

if (!function_exists('add_query_arg')) {
    /** @param array<string, mixed>|string $key */
    function add_query_arg(array|string $key, mixed $value = '', ?string $url = null): string
    {
        return Url::addQueryArg($key, $value, $url);
    }
}

if (!function_exists('remove_query_arg')) {
    /** @param list<string>|string $key */
    function remove_query_arg(array|string $key, ?string $url = null): string
    {
        return Url::removeQueryArg($key, $url);
    }
}

if (!function_exists('get_query_arg')) {
    function get_query_arg(string $key, mixed $default = null, ?string $url = null): mixed
    {
        return Url::getQueryArg($key, $default, $url);
    }
}

/* ---------------------------------------------------------------------------
 * Group 8: Conditional Tags (10.4.9) — bổ sung
 * ------------------------------------------------------------------------- */

if (!function_exists('is_singular')) {
    function is_singular(mixed $postTypes = ''): bool
    {
        return \PrestoWorld\Core\QueryState::isSingular();
    }
}

if (!function_exists('is_attachment')) {
    function is_attachment(mixed $post = ''): bool
    {
        return \PrestoWorld\Core\QueryState::isAttachment();
    }
}

if (!function_exists('is_sticky')) {
    function is_sticky(int $postId = 0): bool
    {
        return \PrestoWorld\Core\QueryState::isSticky();
    }
}

if (!function_exists('is_category')) {
    function is_category(mixed $category = ''): bool
    {
        return \PrestoWorld\Core\QueryState::isCategory();
    }
}

if (!function_exists('is_tag')) {
    function is_tag(mixed $tag = ''): bool
    {
        return \PrestoWorld\Core\QueryState::isTag();
    }
}

if (!function_exists('is_tax')) {
    function is_tax(mixed $taxonomy = '', mixed $term = ''): bool
    {
        return \PrestoWorld\Core\QueryState::isTax();
    }
}

if (!function_exists('is_author')) {
    function is_author(mixed $author = ''): bool
    {
        return \PrestoWorld\Core\QueryState::isAuthor();
    }
}

if (!function_exists('is_date')) {
    function is_date(): bool
    {
        return \PrestoWorld\Core\QueryState::isDate();
    }
}

if (!function_exists('is_feed')) {
    function is_feed(mixed $feeds = ''): bool
    {
        return \PrestoWorld\Core\QueryState::isFeed();
    }
}

if (!function_exists('is_paged')) {
    function is_paged(): bool
    {
        return \PrestoWorld\Core\QueryState::isPaged();
    }
}

if (!function_exists('is_main_query')) {
    function is_main_query(): bool
    {
        return \PrestoWorld\Core\QueryState::isMainQuery();
    }
}

if (!function_exists('is_customize_preview')) {
    function is_customize_preview(): bool
    {
        return \PrestoWorld\Core\QueryState::isCustomizePreview();
    }
}

/* ---------------------------------------------------------------------------
 * Group 9: i18n (10.4.10) — bổ sung
 * ------------------------------------------------------------------------- */

if (!function_exists('_ex')) {
    function _ex(string $text, string $context, string $domain = 'default'): void
    {
        Translator::_ex($text, $context, $domain);
    }
}

if (!function_exists('_nx')) {
    function _nx(string $single, string $plural, int $number, string $context, string $domain = 'default'): string
    {
        return Translator::_nx($single, $plural, $number, $context, $domain);
    }
}

if (!function_exists('_n_noop')) {
    /** @return array{0: string, 1: string, 2: string} */
    function _n_noop(string $singular, string $plural, string $domain = 'default'): array
    {
        return [$singular, $plural, $domain];
    }
}

if (!function_exists('_nx_noop')) {
    /** @return array{0: string, 1: string, 2: string, 3: string} */
    function _nx_noop(string $singular, string $plural, string $context, string $domain = 'default'): array
    {
        return [$singular, $plural, $context, $domain];
    }
}

if (!function_exists('translate')) {
    function translate(string $text, string $domain = 'default'): string
    {
        return Translator::translate($text, $domain);
    }
}

if (!function_exists('translate_with_gettext_context')) {
    function translate_with_gettext_context(string $text, string $context, string $domain = 'default'): string
    {
        return Translator::withContext($text, $context, $domain);
    }
}

if (!function_exists('load_plugin_textdomain')) {
    function load_plugin_textdomain(string $domain, bool $deprecated = false, string $pluginRelPath = ''): bool
    {
        return Translator::loadPluginDomain($domain, $pluginRelPath);
    }
}

if (!function_exists('load_theme_textdomain')) {
    function load_theme_textdomain(string $domain, string|false $path = false): bool
    {
        return Translator::loadThemeDomain($domain, (string) $path);
    }
}

if (!function_exists('load_textdomain')) {
    function load_textdomain(string $domain, string $mofile, bool $deprecated = false): bool
    {
        return Translator::load($domain, $mofile);
    }
}

if (!function_exists('unload_textdomain')) {
    function unload_textdomain(string $domain): void
    {
        Translator::unload($domain);
    }
}

if (!function_exists('is_textdomain_loaded')) {
    function is_textdomain_loaded(string $domain): bool
    {
        return Translator::isLoaded($domain);
    }
}

if (!function_exists('dgettext')) {
    function dgettext(string $domain, string $msgid): string
    {
        return Translator::dget($msgid, $domain);
    }
}

if (!function_exists('dngettext')) {
    function dngettext(string $domain, string $singular, string $plural, int $number): string
    {
        return Translator::dnget($singular, $plural, $number, $domain);
    }
}

if (!function_exists('determine_locale')) {
    function determine_locale(): string
    {
        return Translator::locale();
    }
}