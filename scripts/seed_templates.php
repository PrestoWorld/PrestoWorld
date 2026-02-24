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
            'name' => 'Lux Hotel & Resort',
            'slug' => 'lux-hotel-resort',
            'description' => 'Giao diện đẳng cấp dành cho khách sạn và khu nghỉ dưỡng cao cấp.',
            'category' => 'Du lịch',
            'price' => 249.00,
            'image_url' => 'https://images.unsplash.com/photo-1566073771259-6a8506099945?auto=format&fit=crop&q=80&w=800',
            'demo_url' => 'https://demo.optilarity.top/lux-hotel',
            'features' => json_encode(['Booking System', 'Mobile Optimized', 'SEO Ready']),
            'status' => 'active',
            'created_at' => Chronos::now()
        ],
        [
            'name' => 'Fashionista Store',
            'slug' => 'fashionista-store',
            'description' => 'Mẫu web tối ưu cho các cửa hàng thời trang và phụ kiện.',
            'category' => 'E-Commerce',
            'price' => 189.00,
            'image_url' => 'https://images.unsplash.com/photo-1441986300917-64674bd600d8?auto=format&fit=crop&q=80&w=800',
            'demo_url' => 'https://demo.optilarity.top/fashion-store',
            'features' => json_encode(['Inventory Sync', 'Discount System', 'High Performance']),
            'status' => 'active',
            'created_at' => Chronos::now()
        ]
    ];

    foreach ($templates as $template) {
        $exists = $db->table('optilarity_templates')->where('slug', $template['slug'])->count();
        if ($exists === 0) {
            $db->insert('optilarity_templates')->values($template)->run();
            echo "Seeded: " . $template['name'] . "\n";
        } else {
            echo "Skipped: " . $template['name'] . " (already exists)\n";
        }
    }

} catch (\Throwable $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
