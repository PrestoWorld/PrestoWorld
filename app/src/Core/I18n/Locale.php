<?php

declare(strict_types=1);

namespace PrestoWorld\Core\I18n;

/**
 * Locale — replaces WP_Locale.
 */
class Locale
{
    /** @var list<string> */
    private const WEEKDAYS = [
        'Sunday',
        'Monday',
        'Tuesday',
        'Wednesday',
        'Thursday',
        'Friday',
        'Saturday',
    ];

    /** @var list<string> */
    private const WEEKDAYS_SHORT = [
        'Sun',
        'Mon',
        'Tue',
        'Wed',
        'Thu',
        'Fri',
        'Sat',
    ];

    /** @var list<string> */
    private const MONTHS = [
        'January',
        'February',
        'March',
        'April',
        'May',
        'June',
        'July',
        'August',
        'September',
        'October',
        'November',
        'December',
    ];

    /** @var list<string> */
    private const MONTH_ABBREV = [
        'Jan',
        'Feb',
        'Mar',
        'Apr',
        'May',
        'Jun',
        'Jul',
        'Aug',
        'Sep',
        'Oct',
        'Nov',
        'Dec',
    ];

    /** @var list<string> */
    private const RTL_LANGUAGES = ['ar', 'he', 'fa', 'ur', 'ps', 'sd', 'yi'];

    /** @var array<string, string> */
    private const LOCALE_NAMES = [
        'en_US' => 'English (United States)',
        'en_GB' => 'English (United Kingdom)',
        'en_AU' => 'English (Australia)',
        'en_CA' => 'English (Canada)',
        'fr_FR' => 'Français',
        'de_DE' => 'Deutsch',
        'es_ES' => 'Español',
        'it_IT' => 'Italiano',
        'pt_BR' => 'Português do Brasil',
        'nl_NL' => 'Nederlands',
        'ru_RU' => 'Русский',
        'ja' => '日本語',
        'zh_CN' => '简体中文',
        'ar' => 'العربية',
        'he_IL' => 'עברית',
        'vi' => 'Tiếng Việt',
    ];

    private string $locale;

    public function __construct(string $locale = 'en_US')
    {
        $this->locale = $locale !== '' ? $locale : 'en_US';
    }

    public function get_locale(): string
    {
        return $this->locale;
    }

    public function get_locale_name(): string
    {
        if (isset(self::LOCALE_NAMES[$this->locale])) {
            return self::LOCALE_NAMES[$this->locale];
        }

        $language = strtolower(substr($this->locale, 0, 2));
        if (isset(self::LOCALE_NAMES[$language])) {
            return self::LOCALE_NAMES[$language];
        }

        return $this->locale;
    }

    public function is_rtl(): bool
    {
        $language = strtolower(substr($this->locale, 0, 2));

        return in_array($language, self::RTL_LANGUAGES, true);
    }

    /**
     * @return list<string>
     */
    public function get_weekdays(): array
    {
        return self::WEEKDAYS;
    }

    /**
     * @return list<string>
     */
    public function get_weekdays_short(): array
    {
        return self::WEEKDAYS_SHORT;
    }

    /**
     * @return list<string>
     */
    public function get_months(): array
    {
        return self::MONTHS;
    }

    /**
     * @return list<string>
     */
    public function get_month_abbrev(): array
    {
        return self::MONTH_ABBREV;
    }

    public function get_meridiem(string $meridiem = ''): string
    {
        return match (strtolower($meridiem)) {
            'am' => 'AM',
            'pm' => 'PM',
            default => '',
        };
    }

    public function get_text_direction(): string
    {
        return $this->is_rtl() ? 'rtl' : 'ltr';
    }
}
