<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\HeadlessCMS\Controller;

use Witals\Framework\Http\Request;
use Witals\Framework\Http\Response;
use Witals\Framework\Http\Controllers\AbstractController;
use PrestoWorld\Modules\HeadlessCMS\Serializer\SettingSerializer;
use PrestoWorld\Modules\HeadlessCMS\Transformer\SettingTransformer;

class SettingController extends AbstractController
{
    protected SettingSerializer $settingSerializer;
    protected SettingTransformer $settingTransformer;

    public function __construct(
        SettingSerializer $settingSerializer,
        SettingTransformer $settingTransformer
    ) {
        $this->settingSerializer = $settingSerializer;
        $this->settingTransformer = $settingTransformer;
    }

    /**
     * GET /api/v1/settings - Get site settings
     */
    public function show(Request $request): Response
    {
        $serializer = new SettingSerializer();
        
        // In production, this would fetch from database
        $serialized = $serializer->serialize([
            'site_title' => 'PrestoWorld',
            'site_tagline' => 'A modern headless CMS',
            'site_url' => getenv('SITE_URL') ?: 'http://localhost:8000',
            'home_url' => '/',
            'admin_email' => 'admin@example.com',
            'timezone' => 'UTC',
            'locale' => 'en',
            'date_format' => 'F j, Y',
            'time_format' => 'g:i A',
            'posts_per_page' => 10,
            'default_category' => 1,
            'default_post_format' => 'standard',
        ]);

        return Response::json([
            'success' => true,
            'data' => $this->settingTransformer->transform($serialized),
        ]);
    }
}