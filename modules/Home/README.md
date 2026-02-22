# Home Module - Customization Guide

This module provides a section-based home page that can be customized in any project without modifying the module's source code.

## 🚀 How it works

The `HomeController` renders the home page by gathering "sections" through the `home_sections` filter. Each section has a priority and a callback.

## 🛠️ How to Customize

You can customize the home page from your project's `AppServiceProvider` or any other provider using the `hooks` service.

### 1. Change the Page Title
```php
$hooks->addFilter('home_page_title', function($title) {
    return "My Custom Website Title";
});
```

### 2. Add a New Section
```php
$hooks->addFilter('home_sections', function($sections) {
    $sections['promotion'] = [
        'priority' => 15, // Between Hero (10) and Features (20)
        'callback' => function($request, $section) {
            return "<div class='promo'>Special Offer Only Today!</div>";
        },
        'enabled' => true
    ];
    return $sections;
});
```

### 3. Remove a Default Section
```php
$hooks->addFilter('home_sections', function($sections) {
    unset($sections['recent_posts']);
    return $sections;
});
```

### 4. Customize Section Data
The default sections provide data filters:
```php
$hooks->addFilter('home_hero_data', function($data) {
    $data['title'] = "Welcome to My World";
    $data['cta'] = "Join Now";
    return $data;
});
```

### 5. Wrap Section Output
```php
$hooks->addFilter('home_section_hero_output', function($output, $section) {
    return "<div class='container'>{$output}</div>";
}, 10, 2);
```

## 📍 Path Registration

By default, this module registers the root path `/` to its `HomeController`. 

**Note:** If your project already has a `/` route in `routes/web.php`, you should comment it out to let the module take over:

```php
// routes/web.php
// $router->get('/', ...); // Comment this out
```
