<?php
require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->boot();

try {
    $factory = $app->make(\Witals\Framework\Contracts\View\Factory::class);
    echo "Factory class: " . get_class($factory) . "\n";
    
    $themeManager = $app->make(\PrestoWorld\Theme\ThemeManager::class);
    $themes = $themeManager->all();
    
    // Check theme view
    $html = view('admin/theme/index', ['themes' => $themes, 'tab' => 'all']);
    echo "Theme View works! Length: " . strlen($html) . "\n";
} catch (\Throwable $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "In: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo $e->getTraceAsString() . "\n";
}
