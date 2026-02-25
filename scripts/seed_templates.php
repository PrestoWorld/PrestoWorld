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
        ],
        [
            'name' => 'TechSaaS Pro',
            'slug' => 'techsaas-pro',
            'description' => 'Landing page chuyên nghiệp cho các công ty phần mềm và startup.',
            'category' => 'Công nghệ',
            'price' => 129.00,
            'image_url' => 'https://images.unsplash.com/photo-1460925895917-afdab827c52f?auto=format&fit=crop&q=80&w=800',
            'demo_url' => 'https://demo.optilarity.top/tech-saas',
            'features' => json_encode(['Pricing Tables', 'User Dashboard', 'API Ready']),
            'status' => 'active',
            'created_at' => Chronos::now()
        ],
        [
            'name' => 'Real Estate Elite',
            'slug' => 'real-estate-elite',
            'description' => 'Hệ thống quản lý bất động sản với bộ lọc tìm kiếm thông minh.',
            'category' => 'Bất động sản',
            'price' => 299.00,
            'image_url' => 'https://images.unsplash.com/photo-1560518883-ce09059eeffa?auto=format&fit=crop&q=80&w=800',
            'demo_url' => 'https://demo.optilarity.top/real-estate',
            'features' => json_encode(['Map Integration', 'Advanced Filter', 'Agent Portal']),
            'status' => 'active',
            'created_at' => Chronos::now()
        ],
        [
            'name' => 'Healthy Fit Studio',
            'slug' => 'healthy-fit-studio',
            'description' => 'Giao diện sôi động cho các phòng tập Gym, Yoga và Fitness.',
            'category' => 'Sức khỏe',
            'price' => 149.00,
            'image_url' => 'https://images.unsplash.com/photo-1534438327276-14e5300c3a48?auto=format&fit=crop&q=80&w=800',
            'demo_url' => 'https://demo.optilarity.top/fitness',
            'features' => json_encode(['Class Schedule', 'Member Login', 'Workout Video Support']),
            'status' => 'active',
            'created_at' => Chronos::now()
        ],
        [
            'name' => 'Delicious Bistro',
            'slug' => 'delicious-bistro',
            'description' => 'Mẫu web sang trọng cho nhà hàng với tính năng đặt bàn trực tuyến.',
            'category' => 'Ẩm thực',
            'price' => 169.00,
            'image_url' => 'https://images.unsplash.com/photo-1517248135467-4c7ed9d42339?auto=format&fit=crop&q=80&w=800',
            'demo_url' => 'https://demo.optilarity.top/restaurant',
            'features' => json_encode(['Reservation System', 'Digital Menu', 'Review Slider']),
            'status' => 'active',
            'created_at' => Chronos::now()
        ],
        [
            'name' => 'Creative Agency Flux',
            'slug' => 'creative-agency-flux',
            'description' => 'Portfolio hiện đại cho các Agency sáng tạo và Designer.',
            'category' => 'Sáng tạo',
            'price' => 119.00,
            'image_url' => 'https://images.unsplash.com/photo-1497215728101-856f4ea42174?auto=format&fit=crop&q=80&w=800',
            'demo_url' => 'https://demo.optilarity.top/creative-agency',
            'features' => json_encode(['Smooth Transitions', 'Masonry Gallery', 'Case Studies']),
            'status' => 'active',
            'created_at' => Chronos::now()
        ],
        [
            'name' => 'Education Master',
            'slug' => 'education-master',
            'description' => 'Nền tảng học trực tuyến chuyên nghiệp với quản lý khóa học.',
            'category' => 'Giáo dục',
            'price' => 219.00,
            'image_url' => 'https://images.unsplash.com/photo-1524178232363-1fb2b075b655?auto=format&fit=crop&q=80&w=800',
            'demo_url' => 'https://demo.optilarity.top/education',
            'features' => json_encode(['LMS Ready', 'Student Profile', 'Certificate System']),
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
