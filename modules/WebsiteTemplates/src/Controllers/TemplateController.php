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

    public function __construct(DatabaseProviderInterface $dbal, ThemeManager $theme)
    {
        $this->dbal = $dbal;
        $this->theme = $theme;
    }

    public function index(Request $request): Response
    {
        $category = $request->query('category');
        $search = $request->query('search');

        $query = $this->dbal->database()->select('*')
            ->from('optilarity_templates')
            ->where('status', 'active');

        if ($category) {
            $query->where('category', $category);
        }

        if ($search) {
            $query->where('name', 'LIKE', '%' . $search . '%')
                  ->orWhere('description', 'LIKE', '%' . $search . '%');
        }

        $templates = $query->fetchAll();

        // Get unique categories for filter
        $categories = $this->dbal->database()->select('category')
            ->from('optilarity_templates')
            ->where('status', 'active')
            ->distinct()
            ->fetchAll();

        // If DB is empty, provide some mockup data for immediate show-off
        if (empty($templates) && !$category && !$search) {
            $templates = [
                [
                    'name' => 'E-Commerce Elite',
                    'slug' => 'ecommerce-elite',
                    'description' => 'Giao diện bán hàng chuyên nghiệp tối ưu chuyển đổi.',
                    'price' => 199.00,
                    'image_url' => 'https://images.unsplash.com/photo-1472851294608-062f824d29cc?auto=format&fit=crop&q=80&w=800',
                    'category' => 'Ecommerce'
                ],
                [
                    'name' => 'TechSaaS Landing',
                    'slug' => 'techsaas-landing',
                    'description' => 'Landing page giới thiệu dịch vụ phần mềm hiện đại.',
                    'price' => 149.00,
                    'image_url' => 'https://images.unsplash.com/photo-1460925895917-afdab827c52f?auto=format&fit=crop&q=80&w=800',
                    'category' => 'Technology'
                ],
                [
                    'name' => 'Real Estate Pro',
                    'slug' => 'real-estate-pro',
                    'description' => 'Hệ thống quản lý và giới thiệu bất động sản cao cấp.',
                    'price' => 299.00,
                    'image_url' => 'https://images.unsplash.com/photo-1560518883-ce09059eeffa?auto=format&fit=crop&q=80&w=800',
                    'category' => 'Real Estate'
                ],
                [
                    'name' => 'Fitness Studio',
                    'slug' => 'fitness-studio',
                    'description' => 'Mẫu website cho phòng tập gym và yoga chuyên nghiệp.',
                    'price' => 129.00,
                    'image_url' => 'https://images.unsplash.com/photo-1534438327276-14e5300c3a48?auto=format&fit=crop&q=80&w=800',
                    'category' => 'Health'
                ],
                [
                    'name' => 'Restaurant Master',
                    'slug' => 'restaurant-master',
                    'description' => 'Giao diện đặt món và giới thiệu thực đơn sang trọng.',
                    'price' => 159.00,
                    'image_url' => 'https://images.unsplash.com/photo-1517248135467-4c7ed9d42339?auto=format&fit=crop&q=80&w=800',
                    'category' => 'Food'
                ]
            ];
            
            // Re-populate categories from mock if DB empty
            $categories = array_map(fn($c) => ['category' => $c], array_unique(array_column($templates, 'category')));
        }

        $html = $this->theme->render('templates-list', [
            'title' => __('Website Templates'),
            'templates' => $templates,
            'categories' => $categories,
            'current_category' => $category,
            'search_query' => $search
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
