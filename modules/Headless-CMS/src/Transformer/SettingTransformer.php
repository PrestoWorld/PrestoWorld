<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\HeadlessCMS\Transformer;

use PrestoWorld\Modules\HeadlessCMS\Serializer\SettingSerializer;

class SettingTransformer
{
    /**
     * Transform settings
     */
    public function transform(array $settings): array
    {
        return [
            'id' => 1,
            'attributes' => [
                'site_title' => $settings['site_title'] ?? 'PrestoWorld',
                'site_tagline' => $settings['site_tagline'] ?? '',
                'site_url' => $settings['site_url'] ?? 'http://localhost:8000',
                'home_url' => $settings['home_url'] ?? '/',
                'admin_email' => $settings['admin_email'] ?? 'admin@example.com',
                'timezone' => $settings['timezone'] ?? 'UTC',
                'locale' => $settings['locale'] ?? 'en',
                'date_format' => $settings['date_format'] ?? 'F j, Y',
                'time_format' => $settings['time_format'] ?? 'g:i A',
                'posts_per_page' => (int) ($settings['posts_per_page'] ?? 10),
                'default_category' => (int) ($settings['default_category'] ?? 1),
                'default_post_format' => $settings['default_post_format'] ?? 'standard',
            ],
        ];
    }
}