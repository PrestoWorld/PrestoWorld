# Home Module - Customization Guide

This module provides a section-based home page that can be customized without modifying the module's source code.

## 🛠️ How to Customize

### 1. Change Title
```php
$hooks->addFilter('home_page_title', fn($t) => "Custom Title");
```

### 2. Custom Hero
```php
$hooks->addFilter('home_hero_data', function($data) {
    $data['title'] = "New Hero Text";
    return $data;
});
```

### 3. Add Section
```php
$hooks->addFilter('home_sections', function($sections) {
    $sections['new'] = [
        'priority' => 15,
        'callback' => fn() => "<div>My New Section</div>"
    ];
    return $sections;
});
```

## 📍 Path Registration

Registers root `/`. Ensure you comment out conflicting routes in `routes/web.php`.
