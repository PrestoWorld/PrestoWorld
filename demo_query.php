<?php

declare(strict_types=1);

use App\Foundation\Application;
use PrestoWorld\Modules\Search\PW_Query;
use Cycle\Database\DatabaseInterface;

require_once __DIR__ . '/vendor/autoload.php';

error_reporting(E_ALL);
ini_set('display_errors', '1');

// 1. Initial Application
$app = new Application(__DIR__);
$app->boot();

/** @var DatabaseInterface $db */
$db = $app->make(DatabaseInterface::class);
$prefix = 'pw_';

echo "--- PrestoWorld Query Engine Demo ---\n\n";

try {
    // 2. Seeding Sample Data
    echo "Step 1: Seeding sample data...\n";

    // Clean up first to avoid unique constraint errors in demo
    $db->execute("DELETE FROM {$prefix}posts");
    $db->execute("DELETE FROM {$prefix}post_product");
    $db->execute("DELETE FROM {$prefix}terms");
    $db->execute("DELETE FROM {$prefix}term_relationships");

    // Insert Category
    $termId = $db->insert($prefix . 'terms')->values([
        'taxonomy' => 'product_cat',
        'name' => 'Smartphone',
        'slug' => 'smartphone_demo'
    ])->run();

    // Insert Master Post
    $postId = $db->insert($prefix . 'posts')->values([
        'post_type' => 'product',
        'title' => 'iPhone 16 Pro',
        'slug' => 'iphone-16-pro-demo',
        'status' => 'publish'
    ])->run();

    // Insert Specialized Data
    $db->insert($prefix . 'post_product')->values([
        'post_id' => $postId,
        'price' => 1299.00,
        'sku' => 'IP16PRO-MOCK',
        'stock' => 10
    ])->run();

    // Link Category
    $db->insert($prefix . 'term_relationships')->values([
        'object_id' => $postId,
        'term_id' => $termId
    ])->run();

    echo "Data seeded successfully.\n\n";

    // 3. querying using PW_Query
    echo "Step 2: Querying using pw_query()...\n";

    $query = pw_query([
        'post_type' => 'product',
        'posts_per_page' => 10
    ]);

    if ($query->have_posts()) {
        foreach ($query->posts as $product) {
            echo "---------------------------------\n";
            echo "PRODUCT: " . $product['title'] . "\n";
            echo "PRICE:   $" . number_format((float)$product['price'], 2) . "\n";
            echo "SKU:     " . $product['sku'] . "\n";
            
            if (!empty($product['terms'])) {
                $categories = array_column($product['terms'], 'name');
                echo "CATEGORIES: " . implode(', ', $categories) . "\n";
            }
            echo "---------------------------------\n";
        }
    } else {
        echo "No products found.\n";
    }

} catch (\Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}

echo "\n--- Demo Completed ---\n";
