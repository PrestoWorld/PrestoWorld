<?php

require dirname(__DIR__) . '/vendor/autoload.php';

use Cycle\Database\DatabaseProviderInterface;

$app = require dirname(__DIR__) . '/bootstrap/app.php';
$app->boot();

try {
    $dbal = $app->make(DatabaseProviderInterface::class);
    $db = $dbal->database();

    $sql = "CREATE TABLE IF NOT EXISTS presto_templates (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        slug VARCHAR(255) NOT NULL UNIQUE,
        description TEXT,
        category VARCHAR(100),
        price DECIMAL(10, 2) DEFAULT 0,
        image_url VARCHAR(500),
        demo_url VARCHAR(500),
        features JSON,
        status VARCHAR(20) DEFAULT 'active',
        created_at DATETIME NOT NULL,
        updated_at DATETIME
    )";

    $db->execute($sql);
    echo "Table 'presto_templates' created successfully.\n";

} catch (\Throwable $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
