<?php

declare(strict_types=1);

namespace Modules\WebsiteTemplates\Controllers;

use Witals\Framework\Http\Request;
use Witals\Framework\Http\Response;
use Cycle\Database\DatabaseProviderInterface;
use PrestoWorld\Theme\ThemeManager;

class TemplateController
{
    protected DatabaseProviderInterface $dbal;
    protected ThemeManager $theme;

    public function __construct(mixed $app)
    {
        $this->dbal = $app->make(DatabaseProviderInterface::class);
        $this->theme = $app->make(ThemeManager::class);
    }

    public function index(Request $request): Response
    {
        $templates = $this->dbal->database()->select('*')
            ->from('optilarity_templates')
            ->where('status', 'active')
            ->fetchAll();

        // If DB is empty, provide some mockup data for immediate show-off
        if (empty($templates)) {
            $templates = [
                [
                    'name' => 'E-Commerce Elite',
                    'slug' => 'ecommerce-elite',
                    'description' => 'Giao diện bán hàng chuyên nghiệp tối ưu chuyển đổi.',
                    'price' => 199.00,
                    'image_url' => 'https://images.unsplash.com/photo-1472851294608-062f824d29cc?auto=format&fit=crop&q=80&w=800',
                    'category' => 'Bán hàng'
                ],
                [
                    'name' => 'TechSaaS Landing',
                    'slug' => 'techsaas-landing',
                    'description' => 'Landing page giới thiệu dịch vụ phần mềm hiện đại.',
                    'price' => 149.00,
                    'image_url' => 'https://images.unsplash.com/photo-1460925895917-afdab827c52f?auto=format&fit=crop&q=80&w=800',
                    'category' => 'Công nghệ'
                ],
                [
                    'name' => 'Real Estate Pro',
                    'slug' => 'real-estate-pro',
                    'description' => 'Hệ thống quản lý và giới thiệu bất động sản cao cấp.',
                    'price' => 299.00,
                    'image_url' => 'https://images.unsplash.com/photo-1560518883-ce09059eeffa?auto=format&fit=crop&q=80&w=800',
                    'category' => 'Bất động sản'
                ]
            ];
        }

        $html = $this->theme->render('templates-list', [
            'title' => __('Website Templates'),
            'templates' => $templates
        ]);

        return Response::html($html);
    }

    public function show(Request $request, string $slug): Response
    {
        $template = $this->dbal->database()->select('*')
            ->from('optilarity_templates')
            ->where('slug', $slug)
            ->run()
            ->fetch();

        if (!$template) {
            return Response::html('Template not found', 404);
        }

        $html = $this->theme->render('template-single', [
            'title' => $template['name'],
            'template' => $template
        ]);

        return Response::html($html);
    }
}
