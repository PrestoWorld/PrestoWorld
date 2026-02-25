<?php

require dirname(__DIR__) . '/vendor/autoload.php';

use Cycle\Database\DatabaseProviderInterface;
use Cake\Chronos\Chronos;

// Bootstrap the application properly
$app = require dirname(__DIR__) . '/bootstrap/app.php';
$app->boot();

try {
    $dbal = $app->make(DatabaseProviderInterface::class);
    $db = $dbal->database();

    $templates = [
        [
            'main' => [
                'name' => 'Lux Hotel & Resort',
                'slug' => 'lux-hotel-resort',
                'description' => 'Classy interface for hotels and high-end resorts.',
                'category' => 'Travel',
                'category_slug' => 'travel',
                'price' => 249.00,
                'image_url' => 'https://images.unsplash.com/photo-1566073771259-6a8506099945?auto=format&fit=crop&q=80&w=800',
                'demo_url' => 'https://demo.optilarity.top/lux-hotel',
                'features' => json_encode(['Booking System', 'Mobile Optimized', 'SEO Ready']),
                'status' => 'active',
                'created_at' => Chronos::now()
            ],
            'translations' => [
                'vi' => [
                    'name' => 'Lux Hotel & Resort (VN)',
                    'description' => 'Giao diện đẳng cấp dành cho khách sạn và khu nghỉ dưỡng cao cấp.',
                    'category' => 'Du lịch'
                ]
            ]
        ],
        [
            'main' => [
                'name' => 'Fashionista Store',
                'slug' => 'fashionista-store',
                'description' => 'Optimized web template for fashion and accessory stores.',
                'category' => 'E-Commerce',
                'category_slug' => 'e-commerce',
                'price' => 189.00,
                'image_url' => 'https://images.unsplash.com/photo-1441986300917-64674bd600d8?auto=format&fit=crop&q=80&w=800',
                'demo_url' => 'https://demo.optilarity.top/fashion-store',
                'features' => json_encode(['Inventory Sync', 'Discount System', 'High Performance']),
                'status' => 'active',
                'created_at' => Chronos::now()
            ],
            'translations' => [
                'vi' => [
                    'name' => 'Cửa hàng Thời trang',
                    'description' => 'Mẫu web tối ưu cho các cửa hàng thời trang và phụ kiện.',
                    'category' => 'Thương mại điện tử'
                ]
            ]
        ],
        [
            'main' => [
                'name' => 'TechSaaS Pro',
                'slug' => 'techsaas-pro',
                'description' => 'Professional landing page for software companies and startups.',
                'category' => 'Technology',
                'category_slug' => 'technology',
                'price' => 129.00,
                'image_url' => 'https://images.unsplash.com/photo-1460925895917-afdab827c52f?auto=format&fit=crop&q=80&w=800',
                'demo_url' => 'https://demo.optilarity.top/tech-saas',
                'features' => json_encode(['Pricing Tables', 'User Dashboard', 'API Ready']),
                'status' => 'active',
                'created_at' => Chronos::now()
            ],
            'translations' => [
                'vi' => [
                    'name' => 'TechSaaS Pro',
                    'description' => 'Landing page chuyên nghiệp cho các công ty phần mềm và startup.',
                    'category' => 'Công nghệ'
                ]
            ]
        ]
    ];

    foreach ($templates as $item) {
        $mainData = $item['main'];
        $exists = $db->table('optilarity_templates')->where('slug', $mainData['slug'])->run()->fetch();
        
        if (!$exists) {
            $id = $db->insert('optilarity_templates')->values($mainData)->run();
            echo "Seeded: " . $mainData['name'] . "\n";
        } else {
            $id = $exists['id'];
            $db->update('optilarity_templates', $mainData, ['id' => $id])->run();
            echo "Updated: " . $mainData['name'] . "\n";
        }

        foreach ($item['translations'] as $lang => $transData) {
            $transData['template_id'] = $id;
            $transData['language'] = $lang;
            
            $transExists = $db->table('optilarity_translations_templates')
                ->where('template_id', $id)
                ->where('language', $lang)
                ->count();
            
            if ($transExists === 0) {
                $db->insert('optilarity_translations_templates')->values($transData)->run();
                echo "  -> Seeded translation ($lang) for " . $mainData['name'] . "\n";
            } else {
                $db->update('optilarity_translations_templates', $transData, [
                    'template_id' => $id,
                    'language' => $lang
                ])->run();
                echo "  -> Updated translation ($lang) for " . $mainData['name'] . "\n";
            }
        }
    }

} catch (\Throwable $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
